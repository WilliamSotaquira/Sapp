<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TimeRangeReportExport implements WithMultipleSheets
{
    private $reportData;

    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    public function sheets(): array
    {
        return [
            'Solicitudes' => new RequestsSheet($this->reportData['requests'], $this->reportData['statistics']),
            'Estadísticas' => new StatisticsSheet($this->reportData['statistics'], $this->reportData['dateRange']),
            'Por Familia' => new FamilySheet($this->reportData['groupedData']),
            'Evidencias' => new EvidencesSheet($this->reportData['evidences']),
        ];
    }
}

// Hoja de solicitudes
class RequestsSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    private $requests;
    private $statistics;

    public function __construct($requests, $statistics)
    {
        $this->requests = $requests;
        $this->statistics = $statistics;
    }

    public function collection()
    {
        return $this->requests->map(function ($request, $index) {
            return [
                'No.' => $index + 1,
                'Ticket' => $request->ticket_number,
                'Título' => $request->title,
                'Familia' => $request->subService->service->family->name ?? 'N/A',
                'Servicio' => $request->subService->service->name ?? 'N/A',
                'Subservicio' => $request->subService->name ?? 'N/A',
                'Solicitante' => $request->requester->name ?? 'N/A',
                'Asignado' => $request->assignee->name ?? 'Sin asignar',
                'Estado' => $request->status,
                'Criticidad' => $request->criticality_level,
                'Creado' => $request->created_at->format('d/m/Y H:i'),
                'Fecha Límite' => $request->resolution_deadline
                    ? $request->resolution_deadline->format('d/m/Y H:i')
                    : 'N/A',
                'Resuelto' => $request->resolved_at
                    ? $request->resolved_at->format('d/m/Y H:i')
                    : 'N/A',
                'Tiempo Resolución (min)' => $request->resolved_at
                    ? $request->created_at->diffInMinutes($request->resolved_at)
                    : 'N/A',
                'Satisfacción' => $request->satisfaction_score ?: 'N/A',
                'Evidencias' => $request->evidences->count(),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No.', 'Ticket', 'Título', 'Familia', 'Servicio', 'Subservicio',
            'Solicitante', 'Asignado', 'Estado', 'Criticidad', 'Creado',
            'Fecha Límite', 'Resuelto', 'Tiempo Resolución (min)', 'Satisfacción', 'Evidencias'
        ];
    }

    public function title(): string
    {
        return 'Solicitudes';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E2E8F0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

// Hoja de estadísticas
class StatisticsSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    private $statistics;
    private $dateRange;

    public function __construct($statistics, $dateRange)
    {
        $this->statistics = $statistics;
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        $data = collect();

        // Información general
        $data->push(['Tipo', 'Descripción', 'Valor', 'Porcentaje']);
        $data->push(['', '', '', '']); // Fila vacía

        // Resumen general
        $data->push(['General', 'Periodo del reporte',
            $this->dateRange['start']->format('d/m/Y') . ' - ' . $this->dateRange['end']->format('d/m/Y'), '']);
        $data->push(['General', 'Total de solicitudes', $this->statistics['total'], '100%']);
        $data->push(['General', 'Solicitudes resueltas', $this->statistics['resolvedCount'],
            $this->statistics['total'] > 0
                ? round(($this->statistics['resolvedCount'] / $this->statistics['total']) * 100, 2) . '%'
                : '0%']);
        $data->push(['General', 'Solicitudes vencidas', $this->statistics['overdueCount'], '']);
        $data->push(['General', 'Tiempo prom. resolución (días)', round($this->statistics['avgResolutionTime'], 1), '']);
        $data->push(['General', 'Satisfacción promedio', round($this->statistics['satisfactionAvg'], 2) . '/5', '']);

        $data->push(['', '', '', '']); // Fila vacía

        // Por estado
        foreach ($this->statistics['byStatus'] as $status => $statusData) {
            $data->push(['Estado', $status, $statusData['count'], $statusData['percentage'] . '%']);
        }

        $data->push(['', '', '', '']); // Fila vacía

        // Por criticidad
        foreach ($this->statistics['byCriticality'] as $criticality => $critData) {
            $data->push(['Criticidad', $criticality, $critData['count'], $critData['percentage'] . '%']);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Tipo', 'Descripción', 'Valor', 'Porcentaje'];
    }

    public function title(): string
    {
        return 'Estadísticas';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E2E8F0']],
            ],
        ];
    }
}

// Hoja por familia de servicios
class FamilySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    private $groupedData;

    public function __construct($groupedData)
    {
        $this->groupedData = $groupedData;
    }

    public function collection()
    {
        return collect($this->groupedData)->map(function ($requests, $family) {
            $total = $requests->count();
            $resolved = $requests->where('status', 'RESUELTA')->count();
            $overdue = $requests->filter(function ($request) {
                return $request->resolution_deadline &&
                       $request->resolution_deadline < now() &&
                       !in_array($request->status, ['RESUELTA', 'CERRADA']);
            })->count();

            $avgResolution = $requests->filter(function ($request) {
                return $request->resolved_at && $request->created_at;
            })->avg(function ($request) {
                return $request->created_at->diffInMinutes($request->resolved_at);
            });

            return [
                'Familia' => $family,
                'Total Solicitudes' => $total,
                'Resueltas' => $resolved,
                'Vencidas' => $overdue,
                '% Resolución' => $total > 0 ? round(($resolved / $total) * 100, 2) . '%' : '0%',
                'Tiempo Prom. Resolución (min)' => round($avgResolution ?: 0, 2),
                'Satisfacción Prom.' => round($requests->where('satisfaction_score', '>', 0)->avg('satisfaction_score') ?: 0, 2),
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Familia', 'Total Solicitudes', 'Resueltas', 'Vencidas',
            '% Resolución', 'Tiempo Prom. Resolución (min)', 'Satisfacción Prom.'
        ];
    }

    public function title(): string
    {
        return 'Por Familia';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E2E8F0']],
            ],
        ];
    }
}

// Hoja de evidencias
class EvidencesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    private $evidences;

    public function __construct($evidences)
    {
        $this->evidences = $evidences;
    }

    public function collection()
    {
        return $this->evidences->map(function ($evidence, $index) {
            return [
                'No.' => $index + 1,
                'Solicitud' => $evidence->serviceRequest->ticket_number ?? 'N/A',
                'Título' => $evidence->title,
                'Descripción' => $evidence->description ?: 'Sin descripción',
                'Tipo' => $evidence->evidence_type,
                'Archivo' => $evidence->file_original_name ?: 'Sin archivo',
                'Tamaño' => $evidence->formatted_file_size ?: 'N/A',
                'Subido por' => $evidence->uploadedBy->name ?? 'Sistema',
                'Fecha' => $evidence->created_at->format('d/m/Y H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No.', 'Solicitud', 'Título', 'Descripción', 'Tipo',
            'Archivo', 'Tamaño', 'Subido por', 'Fecha'
        ];
    }

    public function title(): string
    {
        return 'Evidencias';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E2E8F0']],
            ],
        ];
    }
}
