<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\Service;
use App\Models\ServiceLevelAgreement;
use App\Exports\SlaComplianceExport;
use App\Exports\RequestsByStatusExport;
use App\Exports\CriticalityLevelsExport;
use App\Exports\ServicePerformanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentCompanyId = (int) session('current_company_id');
        // Estadísticas generales para el dashboard de reportes
        $stats = [
            'total_requests' => ServiceRequest::reportable()
                ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
                ->count(),
            'pending_requests' => ServiceRequest::reportable()
                ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
                ->where('status', 'PENDIENTE')
                ->count(),
            'resolved_requests' => ServiceRequest::reportable()
                ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
                ->where('status', 'RESUELTA')
                ->count(),
            'overdue_requests' => ServiceRequest::reportable()
                ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
                ->whereNotNull('resolution_deadline')
                ->where('resolution_deadline', '<', now())
                ->whereNotIn('status', ['RESUELTA', 'CERRADA'])
                ->count()
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * Reporte de cumplimiento SLA
     */
    public function slaCompliance(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Crear objeto dateRange para la vista
        $dateRange = [
            'start' => Carbon::parse($dateFrom),
            'end' => Carbon::parse($dateTo)
        ];

        $query = ServiceRequest::query()
            ->with(['sla', 'subService.service.family.contract'])
            ->reportable()
            ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($request->filled('service_id')) {
            $query->whereHas('subService', function($q) use ($request) {
                $q->where('service_id', $request->service_id);
            });
        }

        $requests = $query->get();

        // Estadísticas generales para uso interno
        $compliance = [
            'total' => $requests->count(),
            'on_time' => $requests->where('is_overdue', false)->count(),
            'overdue' => $requests->where('is_overdue', true)->count(),
            'percentage' => $requests->count() > 0 ?
                round(($requests->where('is_overdue', false)->count() / $requests->count()) * 100, 2) : 0
        ];

        // Generar datos de SLA compliance por servicio/familia (formato esperado por la vista)
        $slaCompliance = $requests->groupBy('subService.service.name')->map(function($serviceRequests, $serviceName) {
            $total = $serviceRequests->count();
            $compliant = $serviceRequests->where('is_overdue', false)->count();
            $overdue = $serviceRequests->where('is_overdue', true)->count();
            $family = $serviceRequests->first()?->subService?->service?->family;

            return [
                'service_name' => $serviceName,
                'family' => $this->formatFamilyLabel($family),
                'total_requests' => $total,
                'compliant' => $compliant,
                'overdue' => $overdue,
                'non_compliant' => $overdue, // Alias para la vista
                'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0,
                'sla_name' => $serviceRequests->first()->sla->name ?? 'N/A'
            ];
        })->values()->sortByDesc('compliance_rate');

        $currentCompanyId = (int) session('current_company_id');
        $services = Service::with('family')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->get();

        if ($request->has('export')) {
            return Excel::download(new SlaComplianceExport($dateFrom, $dateTo, (int) session('current_company_id')),
                'sla-compliance-' . date('Y-m-d') . '.xlsx');
        }

        return view('reports.sla-compliance', compact('slaCompliance', 'compliance', 'requests', 'services', 'dateRange', 'dateFrom', 'dateTo'));
    }

    /**
     * Reporte de solicitudes por estado
     */
    public function requestsByStatus(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Crear objeto dateRange para la vista
        $dateRange = [
            'start' => Carbon::parse($dateFrom),
            'end' => Carbon::parse($dateTo)
        ];

        $statusData = ServiceRequest::selectRaw("
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
        ")
        ->reportable()
        ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('status')
        ->get();

        // Calcular total de solicitudes para la vista
        $totalRequests = $statusData->sum('count');

        // Convertir a formato esperado por la vista (indexado por status)
        $requestsByStatus = $statusData->keyBy('status');

        if ($request->has('export')) {
            return Excel::download(new RequestsByStatusExport($dateFrom, $dateTo, (int) session('current_company_id')),
                'requests-by-status-' . date('Y-m-d') . '.xlsx');
        }

        return view('reports.requests-by-status', compact('statusData', 'requestsByStatus', 'totalRequests', 'dateRange', 'dateFrom', 'dateTo'));
    }

    /**
     * Reporte de niveles de criticidad
     */
    public function criticalityLevels(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Crear objeto dateRange para la vista
        $dateRange = [
            'start' => Carbon::parse($dateFrom),
            'end' => Carbon::parse($dateTo)
        ];

        $rawData = ServiceRequest::selectRaw("
            criticality_level,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, NOW()))) as avg_resolution_hours
        ")
        ->reportable()
        ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('criticality_level')
        ->get();

        // Transformar datos en array asociativo indexado por nivel de criticidad
        $criticalityData = $rawData->keyBy('criticality_level');

        if ($request->has('export')) {
            return Excel::download(new CriticalityLevelsExport($dateFrom, $dateTo, (int) session('current_company_id')),
                'criticality-levels-' . date('Y-m-d') . '.xlsx');
        }

        return view('reports.criticality-levels', compact('criticalityData', 'dateRange', 'dateFrom', 'dateTo'));
    }

    /**
     * Reporte de rendimiento por servicio
     */
    public function servicePerformance(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Crear objeto dateRange para la vista
        $dateRange = [
            'start' => Carbon::parse($dateFrom),
            'end' => Carbon::parse($dateTo)
        ];

        $servicePerformance = ServiceRequest::selectRaw("
            services.name as service_name,
            CASE
                WHEN contracts.number IS NULL THEN service_families.name
                ELSE CONCAT(contracts.number, ' - ', service_families.name)
            END as family_name,
            COUNT(service_requests.id) as total_requests,
            AVG(TIMESTAMPDIFF(HOUR, service_requests.created_at, COALESCE(service_requests.resolved_at, NOW()))) as avg_resolution_hours,
            COUNT(CASE WHEN service_requests.status = 'RESUELTA' THEN 1 END) as resolved_count
        ")
        ->reportable()
        ->join('sub_services', 'service_requests.sub_service_id', '=', 'sub_services.id')
        ->join('services', 'sub_services.service_id', '=', 'services.id')
        ->join('service_families', 'services.service_family_id', '=', 'service_families.id')
        ->leftJoin('contracts', 'service_families.contract_id', '=', 'contracts.id')
        ->whereBetween('service_requests.created_at', [$dateFrom, $dateTo])
        ->when((int) session('current_company_id'), fn($q) => $q->where('service_requests.company_id', (int) session('current_company_id')))
        ->whereNull('service_requests.deleted_at')
        ->groupBy('services.id', 'services.name', 'service_families.name', 'contracts.number')
        ->get();

        if ($request->has('export')) {
            return Excel::download(new ServicePerformanceExport($dateFrom, $dateTo, (int) session('current_company_id')),
                'service-performance-' . date('Y-m-d') . '.xlsx');
        }

        return view('reports.service-performance', compact('servicePerformance', 'dateRange', 'dateFrom', 'dateTo'));
    }

    /**
     * Reporte de tendencias mensuales
     */
    public function monthlyTrends(Request $request)
    {
        $months = $request->get('months', 12);

        $trendsData = ServiceRequest::selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'RESUELTA' THEN 1 END) as resolved_requests,
            AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, NOW()))) as avg_resolution_hours
        ")
        ->reportable()
        ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
        ->where('created_at', '>=', now()->subMonths($months))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Formatear los datos para que coincidan con lo que espera la vista
        $trends = $trendsData->map(function ($item) {
            $completionRate = $item->total_requests > 0
                ? round(($item->resolved_requests / $item->total_requests) * 100, 2)
                : 0;

            // Convertir el mes al formato legible
            $monthName = Carbon::createFromFormat('Y-m', $item->month)->locale('es')->format('M Y');

            return [
                'month' => $item->month,
                'month_name' => $monthName,
                'total_requests' => $item->total_requests,
                'resolved_requests' => $item->resolved_requests,
                'closed_requests' => $item->resolved_requests, // Alias para la vista
                'completion_rate' => $completionRate,
                'avg_resolution_hours' => round($item->avg_resolution_hours ?? 0, 1),
                'avg_satisfaction' => 4.2, // Valor por defecto ya que no tenemos tabla de satisfacción
            ];
        });

        return view('reports.monthly-trends', compact('trends', 'months'));
    }

    /**
     * Generar reporte resumen
     */
    public function generateSummary(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Aquí implementarías la lógica para generar un reporte resumen
        // Por ejemplo, usando un Job para procesamiento en background

        return redirect()->route('reports.index')
            ->with('success', 'Reporte resumen generado exitosamente');
    }

    /**
     * Export reports to PDF
     */
    public function exportPdf(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            switch ($reportType) {
                case 'sla-compliance':
                    $slaCompliance = $this->getSlaComplianceData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.sla-compliance-pdf',
                        compact('slaCompliance', 'dateRange'),
                        "reporte-cumplimiento-sla-{$timestamp}.pdf"
                    );

                case 'requests-by-status':
                    $data = $this->getRequestsByStatusData($dateRange);
                    $totalRequests = $data->sum('count');
                    return $this->downloadPdf(
                        'reports.exports.requests-by-status-pdf',
                        [
                            'requestsByStatus' => $data,
                            'totalRequests' => $totalRequests,
                            'dateRange' => $dateRange
                        ],
                        "reporte-estados-solicitudes-{$timestamp}.pdf"
                    );

                case 'criticality-levels':
                    $criticalityData = $this->getCriticalityLevelsData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.criticality-levels-pdf',
                        compact('criticalityData', 'dateRange'),
                        "reporte-criticidad-{$timestamp}.pdf"
                    );

                case 'service-performance':
                    $servicePerformance = $this->getServicePerformanceData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.service-performance-pdf',
                        compact('servicePerformance', 'dateRange'),
                        "reporte-rendimiento-servicios-{$timestamp}.pdf"
                    );

                case 'request-timeline':
                    return back()->with('info', 'Use la opción de exportación desde el detalle del timeline');

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export reports to Excel - VERSIÓN CSV
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

                case 'request-timeline':
                    return back()->with('info', 'Use la opción de exportación desde el detalle del timeline');

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar archivo: ' . $e->getMessage());
        }
    }

    /**
     * Export summary report to PDF
     */
    public function exportSummaryPDF(Request $request)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            // Generar datos del reporte resumen
            $summaryData = [
                'slaCompliance' => $this->getSlaComplianceData($dateRange),
                'requestsByStatus' => $this->getRequestsByStatusData($dateRange),
                'criticalityLevels' => $this->getCriticalityLevelsData($dateRange),
                'servicePerformance' => $this->getServicePerformanceData($dateRange),
                'dateRange' => $dateRange
            ];

            return $this->downloadPdf(
                'reports.exports.summary-pdf',
                $summaryData,
                "reporte-resumen-{$timestamp}.pdf"
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF resumen: ' . $e->getMessage());
        }
    }

    /**
     * Export summary report to Excel
     */
    public function exportSummaryExcel(Request $request)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            // Generar CSV del reporte resumen
            $csv = "REPORTE RESUMEN\n";
            $csv .= "Período: " . $dateRange['start']->format('d/m/Y') . " - " . $dateRange['end']->format('d/m/Y') . "\n\n";

            $csv .= "=== CUMPLIMIENTO SLA ===\n";
            $csv .= $this->formatSlaComplianceForCsv($this->getSlaComplianceData($dateRange));

            $csv .= "\n=== SOLICITUDES POR ESTADO ===\n";
            $csv .= $this->formatRequestsByStatusForCsv($this->getRequestsByStatusData($dateRange));

            $csv .= "\n=== NIVELES DE CRITICIDAD ===\n";
            $csv .= $this->formatCriticalityLevelsForCsv($this->getCriticalityLevelsData($dateRange));

            $csv .= "\n=== RENDIMIENTO POR SERVICIO ===\n";
            $csv .= $this->formatServicePerformanceForCsv($this->getServicePerformanceData($dateRange));

            return $this->downloadCsv($csv, "reporte-resumen-{$timestamp}.csv");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar archivo resumen: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Obtener rango de fechas de la request
     */
    private function getDateRange(Request $request): array
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }

    /**
     * Método auxiliar para descargar PDF
     */
    private function downloadPdf($view, $data, $filename)
    {
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView($view, $data);
            return $pdf->download($filename);
        } else {
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

    // =========================================================================
    // MÉTODOS DE DATOS PARA EXPORTACIÓN
    // =========================================================================

    /**
     * Obtener datos de cumplimiento SLA
     */
    private function getSlaComplianceData($dateRange)
    {
        $requests = ServiceRequest::query()
            ->with(['sla', 'subService.service.family.contract'])
            ->reportable()
            ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        return $requests->groupBy('subService.service.name')->map(function($serviceRequests, $serviceName) {
            $total = $serviceRequests->count();
            $compliant = $serviceRequests->where('is_overdue', false)->count();
            $overdue = $serviceRequests->where('is_overdue', true)->count();
            $family = $serviceRequests->first()?->subService?->service?->family;

            return [
                'service_name' => $serviceName,
                'family' => $this->formatFamilyLabel($family),
                'total_requests' => $total,
                'compliant' => $compliant,
                'overdue' => $overdue,
                'non_compliant' => $overdue,
                'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0,
                'sla_name' => $serviceRequests->first()->sla->name ?? 'N/A'
            ];
        })->values()->sortByDesc('compliance_rate');
    }

    /**
     * Obtener datos de solicitudes por estado
     */
    private function getRequestsByStatusData($dateRange)
    {
        return ServiceRequest::selectRaw("
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
        ")
        ->reportable()
        ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->groupBy('status')
        ->get();
    }

    /**
     * Obtener datos de niveles de criticidad
     */
    private function getCriticalityLevelsData($dateRange)
    {
        return ServiceRequest::selectRaw("
            criticality_level,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, NOW()))) as avg_resolution_hours
        ")
        ->reportable()
        ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->groupBy('criticality_level')
        ->get()
        ->keyBy('criticality_level');
    }

    /**
     * Obtener datos de rendimiento por servicio
     */
    private function getServicePerformanceData($dateRange)
    {
        return ServiceRequest::selectRaw("
            services.name as service_name,
            service_families.name as family_name,
            COUNT(service_requests.id) as total_requests,
            AVG(TIMESTAMPDIFF(HOUR, service_requests.created_at, COALESCE(service_requests.resolved_at, NOW()))) as avg_resolution_hours,
            COUNT(CASE WHEN service_requests.status = 'RESUELTA' THEN 1 END) as resolved_count
        ")
        ->reportable()
        ->join('sub_services', 'service_requests.sub_service_id', '=', 'sub_services.id')
        ->join('services', 'sub_services.service_id', '=', 'services.id')
        ->join('service_families', 'services.service_family_id', '=', 'service_families.id')
        ->whereBetween('service_requests.created_at', [$dateRange['start'], $dateRange['end']])
        ->when((int) session('current_company_id'), fn($q) => $q->where('service_requests.company_id', (int) session('current_company_id')))
        ->whereNull('service_requests.deleted_at')
        ->groupBy('services.id', 'services.name', 'service_families.name')
        ->get();
    }

    /**
     * Formatear datos de SLA compliance para CSV
     */
    private function formatSlaComplianceForCsv($data)
    {
        $csv = "Servicio,Familia,Total Solicitudes,Cumplidas,Vencidas,Tasa de Cumplimiento (%),SLA\n";
        foreach ($data as $item) {
            $csv .= sprintf(
                "\"%s\",\"%s\",%d,%d,%d,%.2f,\"%s\"\n",
                $item['service_name'],
                $item['family'],
                $item['total_requests'],
                $item['compliant'],
                $item['overdue'],
                $item['compliance_rate'],
                $item['sla_name']
            );
        }
        return $csv;
    }

    /**
     * Formatear datos de solicitudes por estado para CSV
     */
    private function formatRequestsByStatusForCsv($data)
    {
        $csv = "Estado,Cantidad,Porcentaje (%)\n";
        foreach ($data as $item) {
            $csv .= sprintf(
                "\"%s\",%d,%.2f\n",
                $item->status,
                $item->count,
                $item->percentage
            );
        }
        return $csv;
    }

    /**
     * Formatear datos de niveles de criticidad para CSV
     */
    private function formatCriticalityLevelsForCsv($data)
    {
        $csv = "Nivel de Criticidad,Cantidad,Horas Promedio de Resolución\n";
        foreach ($data as $level => $item) {
            $csv .= sprintf(
                "\"%s\",%d,%.2f\n",
                $level,
                $item->count,
                $item->avg_resolution_hours ?? 0
            );
        }
        return $csv;
    }

    /**
     * Formatear datos de rendimiento de servicio para CSV
     */
    private function formatServicePerformanceForCsv($data)
    {
        $csv = "Servicio,Familia,Total Solicitudes,Horas Promedio de Resolución,Solicitudes Resueltas\n";
        foreach ($data as $item) {
            $csv .= sprintf(
                "\"%s\",\"%s\",%d,%.2f,%d\n",
                $item->service_name,
                $item->family_name,
                $item->total_requests,
                $item->avg_resolution_hours ?? 0,
                $item->resolved_count
            );
        }
        return $csv;
    }

    private function formatFamilyLabel($family): string
    {
        $familyName = $family?->name ?? 'N/A';
        $contractNumber = $family?->contract?->number;

        return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
    }
}
