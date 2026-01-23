<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ObligacionesExport implements FromArray, WithStyles, WithColumnWidths, WithEvents
{
    private Collection $serviceRequests;

    public function __construct(Collection $serviceRequests)
    {
        $this->serviceRequests = $serviceRequests;
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = [
            'Familia',
            'Obligacion',
            'Actividades Ejecutadas',
            'Productos Presentados'
        ];

        $grouped = $this->serviceRequests->groupBy(function ($sr) {
            return $sr->subService?->service?->family?->name ?? 'Sin Familia';
        })->sortKeys();

        foreach ($grouped as $serviceName => $items) {
            $familyDescription = $items->first()?->subService?->service?->family?->description ?? '';
            $familyCell = $serviceName;
            if ($familyDescription !== '') {
                $familyCell .= "\n" . $familyDescription;
            }
            $first = true;
            foreach ($items as $sr) {
                $rows[] = [
                    $first ? $familyCell : '',
                    $this->stripStatusPrefix($sr->title ?? ''),
                    $this->formatActivities($sr),
                    $this->formatProducts($sr),
                ];
                $first = false;
            }

            $rows[] = ['', '', '', ''];
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
            'A' => 45,
            'B' => 55,
            'C' => 70,
            'D' => 50,
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

                $sheet->freezePane('A2');
            },
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
        foreach ($serviceRequest->evidences as $evidence) {
            if (empty($evidence->file_path)) {
                continue;
            }

            $names[] = $evidence->file_original_name
                ?? $evidence->file_name
                ?? $evidence->title
                ?? 'Evidencia';
        }

        return implode("\n", $names);
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
}
