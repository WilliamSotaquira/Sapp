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
    private string $primaryColor;
    private string $contrastColor;
    private int $headerRowIndex = 0;
    private array $familyRowIndexes = [];
    private array $summaryRowIndexes = [];
    private int $summaryStartRow = 1;
    private int $summaryEndRow = 0;
    private array $familyLinks;

    public function __construct(
        Collection $serviceRequests,
        ?Cut $cut = null,
        array $dateRange = [],
        string $primaryColor = '#1E3A8A',
        string $contrastColor = '#FFFFFF',
        array $familyLinks = []
    )
    {
        $this->serviceRequests = $serviceRequests;
        $this->cut = $cut;
        $this->dateRange = $dateRange;
        $this->primaryColor = $this->normalizeHexColor($primaryColor);
        $this->contrastColor = $this->normalizeHexColor($contrastColor, '#FFFFFF');
        $this->familyLinks = collect($familyLinks)
            ->mapWithKeys(fn ($value, $key) => [(int) $key => trim((string) $value)])
            ->filter(fn ($value, $key) => $key > 0 && $value !== '')
            ->all();
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

        $grouped = $this->serviceRequests
            ->groupBy(function ($sr) {
                $family = $sr->subService?->service?->family;

                return $family?->name ?? 'Sin Familia';
            })
            ->sortBy(function ($items) {
                return (int) ($items->first()?->subService?->service?->family?->sort_order ?? PHP_INT_MAX);
            });

        foreach ($grouped as $serviceName => $items) {
            $familyDescription = $items->first()?->subService?->service?->family?->description ?? '';
            $familyTotal = $items->count();
            $familyId = (int) ($items->first()?->subService?->service?->family?->id ?? 0);
            $familyCloudLink = $this->familyLinks[$familyId] ?? '';
            $familySummary = \App\Support\FamilySummaryBuilder::build($items->values());
            // La fila de familia muestra la descripción y el total de actividades.
            $rows[] = [
                $serviceName,
                $familyDescription,
                'Total actividades',
                $familySummary['actividades']
            ];
            $rowIndex++;
            $this->familyRowIndexes[] = $rowIndex;

            // Fila de resumen del corte para la familia (<=100 palabras).
            // Un solo bloque de texto listo para copiar: la narrativa de lo realizado
            // + el enlace del directorio al final. La etiqueta va en A (subordinada a
            // la familia) y el texto ocupa B:D combinado.
            $resumenNarrativa = trim((string) $familySummary['resumen']);
            $resumenTexto = 'Acciones realizadas (' . $familySummary['actividades'] . '):';
            if ($resumenNarrativa !== '') {
                $resumenTexto .= ' ' . $resumenNarrativa;
            }
            if ($familyCloudLink !== '') {
                $resumenTexto .= ' ' . $familyCloudLink;
            }
            $rows[] = [
                'Resumen del corte',
                $resumenTexto,
                '',
                '',
            ];
            $rowIndex++;
            // Guardar el texto para calcular la altura de la fila (las celdas
            // combinadas no ajustan su alto automáticamente en PhpSpreadsheet).
            $this->summaryRowIndexes[$rowIndex] = $resumenTexto;

            $first = true;
            foreach ($items as $sr) {
                $rows[] = [
                    '',
                    $this->formatObligation($sr),
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
                        ->getFont()
                        ->setBold(true)
                        ->getColor()
                        ->setARGB($this->hexToArgb($this->contrastColor));
                    $sheet->getStyle("A{$this->headerRowIndex}:D{$this->headerRowIndex}")
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($this->hexToArgb($this->primaryColor));
                }

                // Estilo del bloque de resumen
                if ($this->summaryEndRow >= $this->summaryStartRow) {
                    $sheet->getStyle("A{$this->summaryStartRow}:A{$this->summaryEndRow}")
                        ->getFont()->setBold(true);
                }

                // Estilo de filas de familia
                foreach ($this->familyRowIndexes as $row) {
                    $sheet->getStyle("A{$row}:D{$row}")
                        ->getFont()
                        ->setBold(true)
                        ->getColor()
                        ->setARGB($this->hexToArgb($this->contrastColor));
                    $sheet->getStyle("A{$row}:D{$row}")
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($this->hexToArgb($this->primaryColor));
                }

                // Estilo de las filas de resumen del corte por familia.
                // Ancho combinado B:D en unidades de columna (B=55, C=70, D=45).
                $mergedWidth = $this->columnWidths()['B']
                    + $this->columnWidths()['C']
                    + $this->columnWidths()['D'];
                // Aproximación: ~1.05 caracteres por unidad de ancho de columna.
                $charsPerLine = max(1, (int) floor($mergedWidth * 1.05));

                foreach ($this->summaryRowIndexes as $row => $texto) {
                    $sheet->mergeCells("B{$row}:D{$row}");
                    $sheet->getStyle("A{$row}:D{$row}")
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFEFF6FF');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$row}")->getFont()->setItalic(true);
                    $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);

                    // Calcular alto: nº de líneas (por saltos de línea y por wrap) * alto de línea.
                    $lineas = 0;
                    foreach (preg_split('/\r\n|\r|\n/', (string) $texto) as $parrafo) {
                        $len = max(1, mb_strlen($parrafo));
                        $lineas += (int) ceil($len / $charsPerLine);
                    }
                    $lineas = max(1, $lineas);
                    // ~15 pt por línea; margen extra para que no corte.
                    $sheet->getRowDimension($row)->setRowHeight($lineas * 15 + 4);
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
        return $this->extractResolutionDescription((string) ($serviceRequest->resolution_notes ?? ''));
    }

    private function formatObligation(ServiceRequest $serviceRequest): string
    {
        $lines = [];

        if (!empty($serviceRequest->ticket_number)) {
            $lines[] = 'Solicitud: ' . $serviceRequest->ticket_number;
        }

        $title = $this->stripStatusPrefix((string) ($serviceRequest->title ?? ''));
        if ($title !== '') {
            $lines[] = $title;
        }

        return implode("\n", $lines);
    }

    private function formatProducts(ServiceRequest $serviceRequest): string
    {
        if (!$serviceRequest->relationLoaded('evidences')) {
            return '';
        }

        $names = [];
        foreach ($serviceRequest->evidences as $evidence) {
            if (!empty($evidence->file_path)) {
                $names[] = $this->resolveEvidenceFileName($evidence);
                continue;
            }

            if (($evidence->evidence_type ?? null) !== 'ENLACE') {
                continue;
            }

            $url = trim((string) ($evidence->evidence_data['url'] ?? $evidence->description ?? ''));
            if ($url === '') {
                continue;
            }

            $url = $this->normalizeExternalUrl($url);

            $label = trim((string) ($evidence->title ?? ''));
            $names[] = ($label !== '' && !str_starts_with($label, 'Enlace - '))
                ? "{$label}: {$url}"
                : $url;
        }

        $names = array_values(array_filter($names));

        return implode("\n", $names);
    }

    private function resolveEvidenceFileName($evidence): string
    {
        return trim((string) (
            $evidence->file_original_name
            ?? $evidence->file_name
            ?? ''
        ));
    }

    private function normalizeExternalUrl(string $url): string
    {
        return preg_replace('/ovprdnwportwebapp01/i', 'www', $url) ?? $url;
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
            'NO_VIABLE',
        ];

        $pattern = '/^.+?\s-\s(' . implode('|', $statuses) . ')\s-\s/i';

        return preg_replace($pattern, '', $title) ?? $title;
    }

    private function extractResolutionDescription(string $notes): string
    {
        $notes = trim($notes);
        if ($notes === '') {
            return '';
        }

        $notes = preg_replace('/\s*===\s*CIERRE(?:\s+POR\s+VENCIMIENTO|\s+NORMAL)\s*===.*$/is', '', $notes) ?? $notes;
        $notes = preg_replace('/^\s*Fecha\/Hora:.*$/im', '', $notes) ?? $notes;
        $notes = preg_replace('/^\s*Usuario:\s*ID\s*\d+.*$/im', '', $notes) ?? $notes;
        $notes = trim($notes);

        if (preg_match('/Acciones realizadas:\s*(.*?)(?:\n\s*Notas adicionales:\s*|$)/is', $notes, $matches)) {
            $notes = trim((string) ($matches[1] ?? ''));
        }

        $notes = preg_replace('/\n{3,}/', "\n\n", $notes) ?? $notes;

        return trim($notes);
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

    private function normalizeHexColor(string $value, string $fallback = '#1E3A8A'): string
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^#([A-F0-9]{6})$/', $value)) {
            return $value;
        }

        return $fallback;
    }

    private function hexToArgb(string $hex): string
    {
        return 'FF' . ltrim($hex, '#');
    }

}
