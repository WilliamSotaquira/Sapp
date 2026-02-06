<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ServicePerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $companyId;

    public function __construct($startDate = null, $endDate = null, $companyId = null)
    {
        $this->startDate = $startDate ?: Carbon::now()->subDays(30);
        $this->endDate = $endDate ?: Carbon::now();
        $this->companyId = $companyId;
    }

    public function collection()
    {
        $companyId = $this->companyId ?? (int) session('current_company_id');
        $data = ServiceRequest::with(['subService.service.family'])
            ->reportable()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get()
            ->groupBy(function ($request) {
                $family = $request->subService?->service?->family;
                $familyName = $family?->name ?? 'N/A';
                $contractNumber = $family?->contract?->number;
                return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
            })
            ->map(function ($requests, $familyName) {
                $totalRequests = $requests->count();
                $avgResolutionTime = $requests->whereNotNull('resolved_at')
                    ->whereNotNull('accepted_at')
                    ->avg(function ($request) {
                        return $request->accepted_at->diffInMinutes($request->resolved_at);
                    });

                $satisfactionRate = $requests->whereNotNull('satisfaction_score')
                    ->avg('satisfaction_score');

                return (object) [
                    'family' => $familyName,
                    'total_requests' => $totalRequests,
                    'avg_resolution_time' => round($avgResolutionTime ?? 0, 2),
                    'avg_satisfaction' => round($satisfactionRate ?? 0, 2),
                    'services_count' => $requests->groupBy('subService.service.name')->count()
                ];
            })
            ->sortByDesc('total_requests')
            ->values();

        return $data;
    }

    public function headings(): array
    {
        return [
            'Familia de Servicio',
            'Cantidad de Servicios',
            'Total Solicitudes',
            'Tiempo Resolución Promedio (min)',
            'Satisfacción Promedio'
        ];
    }

    public function map($performance): array
    {
        return [
            $performance->family,
            $performance->services_count,
            $performance->total_requests,
            $performance->avg_resolution_time,
            $performance->avg_satisfaction . '/5'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
