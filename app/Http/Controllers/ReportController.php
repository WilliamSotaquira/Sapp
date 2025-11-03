<?php

namespace App\Http\Controllers;

use App\Models\ServiceFamily;
use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceRequest;
use App\Models\ServiceLevelAgreement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Display the main reports dashboard
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * SLA Compliance Report
     */
    public function slaCompliance(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $slaCompliance = ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('sla.serviceFamily.name')
            ->map(function ($requests, $familyName) {
                $total = $requests->count();
                $compliant = $requests->filter(function ($request) {
                    return $this->isSlaCompliant($request);
                })->count();

                return [
                    'family' => $familyName,
                    'total_requests' => $total,
                    'compliant' => $compliant,
                    'non_compliant' => $total - $compliant,
                    'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();

        return view('reports.sla-compliance', compact('slaCompliance', 'dateRange'));
    }

    /**
     * Service Requests by Status Report
     */
    public function requestsByStatus(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $requestsByStatus = ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalRequests = $requestsByStatus->sum('count');

        return view('reports.requests-by-status', compact('requestsByStatus', 'totalRequests', 'dateRange'));
    }

    /**
     * Criticality Level Report
     */
    public function criticalityLevels(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $criticalityData = ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get()
            ->keyBy('criticality_level');

        return view('reports.criticality-levels', compact('criticalityData', 'dateRange'));
    }

    /**
     * Service Performance Report
     */
    public function servicePerformance(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $servicePerformance = ServiceRequest::with(['subService.service.family'])
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

            $monthData = ServiceRequest::whereBetween('created_at', [$startDate, $endDate])
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
     * Export reports to PDF - VERSIÓN MEJORADA
     */
    public function exportPdf(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            switch ($reportType) {
                case 'sla-compliance':
                    $slaCompliance = $this->getSlaComplianceData($dateRange);
                    return $this->downloadPdf('reports.exports.sla-compliance-pdf',
                        compact('slaCompliance', 'dateRange'),
                        "reporte-cumplimiento-sla-{$timestamp}.pdf");

                case 'requests-by-status':
                    $data = $this->getRequestsByStatusData($dateRange);
                    $totalRequests = $data->sum('count');
                    return $this->downloadPdf('reports.exports.requests-by-status-pdf',
                        [
                            'requestsByStatus' => $data,
                            'totalRequests' => $totalRequests,
                            'dateRange' => $dateRange
                        ],
                        "reporte-estados-solicitudes-{$timestamp}.pdf");

                case 'criticality-levels':
                    $criticalityData = $this->getCriticalityLevelsData($dateRange);
                    return $this->downloadPdf('reports.exports.criticality-levels-pdf',
                        compact('criticalityData', 'dateRange'),
                        "reporte-criticidad-{$timestamp}.pdf");

                case 'service-performance':
                    $servicePerformance = $this->getServicePerformanceData($dateRange);
                    return $this->downloadPdf('reports.exports.service-performance-pdf',
                        compact('servicePerformance', 'dateRange'),
                        "reporte-rendimiento-servicios-{$timestamp}.pdf");

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export reports to Excel - VERSIÓN CSV (FUNCIONAL INMEDIATA)
     */
    public function exportExcel(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            switch ($reportType) {
                case 'sla-compliance':
                    $data = $this->getSlaComplianceData($dateRange);
                    $csv = $this->formatSlaComplianceForCsv($data);
                    return $this->downloadCsv($csv, "reporte-cumplimiento-sla-{$timestamp}.csv");

                case 'requests-by-status':
                    $data = $this->getRequestsByStatusData($dateRange);
                    $csv = $this->formatRequestsByStatusForCsv($data);
                    return $this->downloadCsv($csv, "reporte-estados-solicitudes-{$timestamp}.csv");

                case 'criticality-levels':
                    $data = $this->getCriticalityLevelsData($dateRange);
                    $csv = $this->formatCriticalityLevelsForCsv($data);
                    return $this->downloadCsv($csv, "reporte-criticidad-{$timestamp}.csv");

                case 'service-performance':
                    $data = $this->getServicePerformanceData($dateRange);
                    $csv = $this->formatServicePerformanceForCsv($data);
                    return $this->downloadCsv($csv, "reporte-rendimiento-servicios-{$timestamp}.csv");

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar archivo: ' . $e->getMessage());
        }
    }

    /**
     * Método auxiliar para descargar PDF
     */
    private function downloadPdf($view, $data, $filename)
    {
        // Intentar usar DomPDF si está disponible
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
            return $pdf->download($filename);
        } else {
            // Fallback a HTML
            $html = view($view, $data)->render();
            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }
    }

    /**
     * Método auxiliar para descargar CSV
     */
    private function downloadCsv($csv, $filename)
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Métodos auxiliares para obtener datos
     */
    private function getSlaComplianceData($dateRange)
    {
        return ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('sla.serviceFamily.name')
            ->map(function ($requests, $familyName) {
                $total = $requests->count();
                $compliant = $requests->filter(function ($request) {
                    return $this->isSlaCompliant($request);
                })->count();

                return [
                    'family' => $familyName,
                    'total_requests' => $total,
                    'compliant' => $compliant,
                    'non_compliant' => $total - $compliant,
                    'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();
    }

    private function getRequestsByStatusData($dateRange)
    {
        return ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }

    private function getCriticalityLevelsData($dateRange)
    {
        return ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get();
    }

    private function getServicePerformanceData($dateRange)
    {
        return ServiceRequest::with(['subService.service.family'])
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
     * Métodos para formatear datos para CSV
     */
    private function formatSlaComplianceForCsv($data)
    {
        $csv = "Familia de Servicio,Total Solicitudes,Cumplidas,Incumplidas,Tasa de Cumplimiento (%)\n";

        foreach ($data as $item) {
            $csv .= "\"{$item['family']}\",{$item['total_requests']},{$item['compliant']},{$item['non_compliant']},{$item['compliance_rate']}\n";
        }

        return $csv;
    }

    private function formatRequestsByStatusForCsv($data)
    {
        $total = $data->sum('count');
        $csv = "Estado,Cantidad,Porcentaje\n";

        foreach ($data as $item) {
            $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
            $csv .= "\"{$item->status}\",{$item->count},{$percentage}\n";
        }

        return $csv;
    }

    private function formatCriticalityLevelsForCsv($data)
    {
        $total = $data->sum('count');
        $csv = "Nivel de Criticidad,Cantidad,Porcentaje\n";

        foreach ($data as $item) {
            $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
            $csv .= "\"{$item->criticality_level}\",{$item->count},{$percentage}\n";
        }

        return $csv;
    }

    private function formatServicePerformanceForCsv($data)
    {
        $csv = "Familia de Servicio,Cantidad de Servicios,Total Solicitudes,Tiempo Resolución Promedio (min),Satisfacción Promedio\n";

        foreach ($data as $item) {
            $csv .= "\"{$item['family']}\",{$item['services_count']},{$item['total_requests']},{$item['avg_resolution_time']},{$item['avg_satisfaction']}\n";
        }

        return $csv;
    }

    /**
     * Check if a service request is SLA compliant
     */
    private function isSlaCompliant(ServiceRequest $request): bool
    {
        if (!$request->sla) return false;

        $compliant = true;

        if ($request->accepted_at && $request->acceptance_deadline) {
            if ($request->accepted_at->gt($request->acceptance_deadline)) {
                $compliant = false;
            }
        }

        if ($request->responded_at && $request->response_deadline) {
            if ($request->responded_at->gt($request->response_deadline)) {
                $compliant = false;
            }
        }

        if ($request->resolved_at && $request->resolution_deadline) {
            if ($request->resolved_at->gt($request->resolution_deadline)) {
                $compliant = false;
            }
        }

        return $compliant;
    }

    /**
     * Get date range from request or default to last 30 days
     */
    private function getDateRange(Request $request): array
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : Carbon::now()->subDays(30);

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))->endOfDay()
            : Carbon::now();

        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }
}
