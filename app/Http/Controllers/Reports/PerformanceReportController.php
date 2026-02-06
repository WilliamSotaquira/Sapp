<?php

namespace App\Http\Controllers\Reports;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class PerformanceReportController extends ReportController
{
    /**
     * Service Performance Report
     */
    public function servicePerformance(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $servicePerformance = ServiceRequest::with(['subService.service.family'])
            ->reportable()
            ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('subService.service.family.name')
            ->map(function ($requests, $familyName) {
                $totalRequests = $requests->count();
                $avgResolutionTime = $requests->whereNotNull('resolved_at')
                    ->whereNotNull('accepted_at')
                    ->avg(function ($request) {
                        return $request->accepted_at->diffInMinutes($request->resolved_at);
                    });

                $satisfactionRate = $requests->whereNotNull('satisfaction_score')
                    ->avg('satisfaction_score');

                return [
                    'family' => $familyName,
                    'total_requests' => $totalRequests,
                    'avg_resolution_time' => round($avgResolutionTime ?? 0, 2),
                    'avg_satisfaction' => round($satisfactionRate ?? 0, 2),
                    'services_count' => $requests->groupBy('subService.service.name')->count()
                ];
            })
            ->sortByDesc('total_requests')
            ->values();

        return view('reports.service-performance', compact('servicePerformance', 'dateRange'));
    }

    /**
     * Monthly Trends Report
     */
    public function monthlyTrends(Request $request)
    {
        $months = 6;
        $trends = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $monthData = ServiceRequest::reportable()
                ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "CERRADA" THEN 1 ELSE 0 END) as closed_requests,
                    AVG(CASE WHEN satisfaction_score IS NOT NULL THEN satisfaction_score ELSE NULL END) as avg_satisfaction
                ')
                ->first();

            $trends->push([
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->translatedFormat('F Y'),
                'total_requests' => $monthData->total_requests,
                'closed_requests' => $monthData->closed_requests,
                'completion_rate' => $monthData->total_requests > 0
                    ? round(($monthData->closed_requests / $monthData->total_requests) * 100, 2)
                    : 0,
                'avg_satisfaction' => round($monthData->avg_satisfaction ?? 0, 2)
            ]);
        }

        return view('reports.monthly-trends', compact('trends'));
    }

    /**
     * Obtener datos para Service Performance
     */
    public function getServicePerformanceData($dateRange)
    {
        return ServiceRequest::with(['subService.service.family'])
            ->reportable()
            ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('subService.service.family.name')
            ->map(function ($requests, $familyName) {
                $totalRequests = $requests->count();
                $avgResolutionTime = $requests->whereNotNull('resolved_at')
                    ->whereNotNull('accepted_at')
                    ->avg(function ($request) {
                        return $request->accepted_at->diffInMinutes($request->resolved_at);
                    });

                $satisfactionRate = $requests->whereNotNull('satisfaction_score')
                    ->avg('satisfaction_score');

                return [
                    'family' => $familyName,
                    'total_requests' => $totalRequests,
                    'avg_resolution_time' => round($avgResolutionTime ?? 0, 2),
                    'avg_satisfaction' => round($satisfactionRate ?? 0, 2),
                    'services_count' => $requests->groupBy('subService.service.name')->count()
                ];
            })
            ->sortByDesc('total_requests')
            ->values();
    }

    /**
     * Formatear datos para CSV - Performance
     */
    public function formatServicePerformanceForCsv($data)
    {
        $csv = "Familia de Servicio,Cantidad de Servicios,Total Solicitudes,Tiempo Resolución Promedio (min),Satisfacción Promedio\n";
        foreach ($data as $item) {
            $csv .= "\"{$item['family']}\",{$item['services_count']},{$item['total_requests']},{$item['avg_resolution_time']},{$item['avg_satisfaction']}\n";
        }
        return $csv;
    }
}
