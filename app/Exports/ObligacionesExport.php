<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Illuminate\Support\Collection;
use App\Models\Cut;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ObligacionesExport implements FromArray, WithStyles, WithColumnWidths, WithEvents, WithColumnFormatting
{
    private Collection $serviceRequests;
    private ?Cut $cut;
    private array $dateRange;
    private int $headerRowIndex = 0;
    private array $familyRowIndexes = [];
    private int $summaryStartRow = 1;
    private int $summaryEndRow = 0;

    public function __construct(Collection $serviceRequests, ?Cut $cut = null, array $dateRange = [])
    {
        $this->serviceRequests = $serviceRequests;
        $this->cut = $cut;
        $this->dateRange = $dateRange;
    }

    public function array(): array
    {
        $rows = [];
        $rowIndex = 0;

        $rangeStart = $this->dateRange['start'] ?? null;
        $rangeEnd = $this->dateRange['end'] ?? null;
        $rangeLabel = '';
        if ($rangeStart || $rangeEnd) {
            $rangeLabel = ($rangeStart ? $rangeStart->format('Y-m-d') : '') . ' - ' . ($rangeEnd ? $rangeEnd->format('Y-m-d') : '');
        }
        $totalAcciones = $this->serviceRequests->count();
        $headerLabel = $this->resolveContractPeriodLabel();

        $rows[] = ['Contrato y periodo', $headerLabel, '', '']; $rowIndex++;
        $rows[] = ['Rango', $rangeLabel, '', '']; $rowIndex++;
        $rows[] = ['Total acciones', $totalAcciones, '', '']; $rowIndex++;
        $this->summaryEndRow = $rowIndex;
        $rows[] = ['', '', '', '']; $rowIndex++;
        $rows[] = ['Familia', 'Obligacion', 'Actividades Ejecutadas', 'Productos Presentados']; $rowIndex++;
        $this->headerRowIndex = $rowIndex;

        $grouped = $this->serviceRequests->groupBy(function ($sr) {
            $family = $sr->subService?->service?->family;
            $familyName = $family?->name ?? 'Sin Familia';
            $contractNumber = $family?->contract?->number;
            return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
        })->sortKeys();

        foreach ($grouped as $serviceName => $items) {
            $familyDescription = $items->first()?->subService?->service?->family?->description ?? '';
            $familyTotal = $items->count();
            $rows[] = [
                $serviceName,
                $familyDescription,
                'Total acciones',
                $familyTotal
            ];
            $rowIndex++;
            $this->familyRowIndexes[] = $rowIndex;

            $first = true;
            foreach ($items as $sr) {
                $rows[] = [
                    '',
                    $this->stripStatusPrefix($sr->title ?? ''),
                    $this->formatActivities($sr),
                    $this->formatProducts($sr),
                ];
                $first = false;
                $rowIndex++;
            }

            $rows[] = ['', '', '', '']; $rowIndex++;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 55,
            'C' => 70,
            'D' => 45,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $range = "A1:D{$highestRow}";

                $sheet->getStyle($range)
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
                    ->setWrapText(true);

                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(
                    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                );

                if ($this->headerRowIndex > 0) {
                    $sheet->freezePane('A' . ($this->headerRowIndex + 1));
                }

                // Estilo del encabezado de columnas
                if ($this->headerRowIndex > 0) {
                    $sheet->getStyle("A{$this->headerRowIndex}:D{$this->headerRowIndex}")
                        ->getFont()->setBold(true);
                    $sheet->getStyle("A{$this->headerRowIndex}:D{$this->headerRowIndex}")
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE5E7EB');
                }

                // Estilo del bloque de resumen
                if ($this->summaryEndRow >= $this->summaryStartRow) {
                    $sheet->getStyle("A{$this->summaryStartRow}:A{$this->summaryEndRow}")
                        ->getFont()->setBold(true);
                }

                // Estilo de filas de familia
                foreach ($this->familyRowIndexes as $row) {
                    $sheet->getStyle("A{$row}:D{$row}")
                        ->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}:D{$row}")
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFDBEAFE');
                }

                // Convertir enlaces en la columna D (Productos Presentados)
                $highestRow = $sheet->getHighestRow();
                for ($row = $this->headerRowIndex + 1; $row <= $highestRow; $row++) {
                    $cell = $sheet->getCell("D{$row}");
                    $value = (string) $cell->getValue();
                    if ($value === '') {
                        continue;
                    }

                    $firstUrl = $this->extractFirstUrl($value);
                    if ($firstUrl) {
                        $cell->getHyperlink()->setUrl($firstUrl);
                        $sheet->getStyle("D{$row}")
                            ->getFont()
                            ->getColor()
                            ->setARGB('FF2563EB');
                        $sheet->getStyle("D{$row}")
                            ->getFont()
                            ->setUnderline(true);
                    }
                }
            },
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    private function formatActivities(ServiceRequest $serviceRequest): string
    {
        if (!$serviceRequest->relationLoaded('tasks')) {
            return '';
        }

        $lines = [];

        foreach ($serviceRequest->tasks as $task) {
            $taskTitle = $task->title ?? 'Tarea';
            $lines[] = $taskTitle;
            $subtaskTitles = [];

            if ($task->relationLoaded('subtasks')) {
                foreach ($task->subtasks as $subtask) {
                    if (!empty($subtask->title)) {
                        $subtaskTitles[] = $subtask->title;
                    }
                }
            }

            if (!empty($subtaskTitles)) {
                foreach ($subtaskTitles as $subtaskTitle) {
                    $lines[] = '  - ' . $subtaskTitle;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function formatProducts(ServiceRequest $serviceRequest): string
    {
        if (!$serviceRequest->relationLoaded('evidences')) {
            return '';
        }

        $names = [];
        $urls = [];
        foreach ($serviceRequest->evidences as $evidence) {
            if (empty($evidence->file_path)) {
                continue;
            }

            $names[] = $evidence->file_original_name
                ?? $evidence->file_name
                ?? $evidence->title
                ?? 'Evidencia';

            if (!empty($evidence->file_url)) {
                $urls[] = $evidence->file_url;
            }
        }

        $output = implode("\n", $names);
        if (!empty($urls)) {
            $output .= "\n" . implode("\n", $urls);
        }
        return $output;
    }

    private function stripStatusPrefix(string $title): string
    {
        if ($title === '') {
            return $title;
        }

        $statuses = [
            'PENDIENTE',
            'ACEPTADA',
            'EN_PROCESO',
            'RESUELTA',
            'CERRADA',
            'CANCELADA',
            'PAUSADA',
            'REABIERTO',
            'RECHAZADA',
        ];

        $pattern = '/^.+?\s-\s(' . implode('|', $statuses) . ')\s-\s/i';

        return preg_replace($pattern, '', $title) ?? $title;
    }

    private function resolveContractPeriodLabel(): string
    {
        $contractNumber = $this->cut?->contract?->number;
        if (empty($contractNumber)) {
            $contractNumbers = $this->serviceRequests
                ->pluck('subService.service.family.contract.number')
                ->filter()
                ->unique()
                ->values();

            if ($contractNumbers->count() === 1) {
                $contractNumber = (string) $contractNumbers->first();
            } elseif ($contractNumbers->count() > 1) {
                $contractNumber = 'Varios contratos';
            } else {
                $contractNumber = 'Sin contrato';
            }
        }

        $periodLabel = $this->cut?->name;
        if (empty($periodLabel) && !empty($this->dateRange['start'])) {
            $periodLabel = ucfirst($this->dateRange['start']->locale('es')->translatedFormat('F Y'));
        }
        if (empty($periodLabel)) {
            $periodLabel = 'Periodo';
        }

        return $contractNumber . ': ' . $periodLabel;
    }

    private function extractFirstUrl(string $value): ?string
    {
        if (preg_match('/https?:\\/\\/[^\\s]+/i', $value, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
