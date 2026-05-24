<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Requester;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ServicesSlaController extends Controller
{
    /**
     * Display the unified Services and SLA report.
     *
     * Combines SLA compliance rates (grouped by service/family) and
     * performance metrics (total requests, avg resolution hours, resolved count).
     *
     * Filters: date_from, date_to (default last 30 days), requester_id, department
     */
    public function index(Request $request): View
    {
        $dateRange = $this->getDateRange($request);
        $currentCompanyId = (int) session('current_company_id');
        $requesterId = $request->filled('requester_id') ? (int) $request->input('requester_id') : null;
        $department = $request->filled('department') ? trim((string) $request->input('department')) : null;

        // SLA Compliance data grouped by service and family
        $slaData = $this->getSlaComplianceData($dateRange, $currentCompanyId, $requesterId, $department);

        // Performance metrics per service
        $performanceData = $this->getServicePerformanceData($dateRange, $currentCompanyId, $requesterId, $department);

        // Filter options
        $requesters = Requester::query()
            ->select('id', 'name', 'email')
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->orderBy('name')
            ->get();

        $departments = Requester::query()
            ->select('department')
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('reports.services-sla.index', compact(
            'slaData',
            'performanceData',
            'dateRange',
            'requesters',
            'departments',
            'requesterId',
            'department'
        ));
    }

    /**
     * Export the Services and SLA report in the specified format.
     *
     * Supported formats: pdf, csv
     */
    public function export(Request $request, string $format): Response
    {
        $dateRange = $this->getDateRange($request);
        $currentCompanyId = (int) session('current_company_id');
        $requesterId = $request->filled('requester_id') ? (int) $request->input('requester_id') : null;
        $department = $request->filled('department') ? trim((string) $request->input('department')) : null;

        $slaData = $this->getSlaComplianceData($dateRange, $currentCompanyId, $requesterId, $department);
        $performanceData = $this->getServicePerformanceData($dateRange, $currentCompanyId, $requesterId, $department);

        $timestamp = now()->format('Y-m-d_His');

        try {
            if ($format === 'pdf') {
                return $this->exportPdf($slaData, $performanceData, $dateRange, $timestamp);
            }

            if ($format === 'csv') {
                return $this->exportCsv($slaData, $performanceData, $dateRange, $timestamp);
            }

            return back()->with('error', 'Formato de exportación no válido. Use pdf o csv.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar la exportación: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATA RETRIEVAL METHODS
    // =========================================================================

    /**
     * Get SLA compliance data grouped by service and family.
     *
     * Reuses logic from ReportController::slaCompliance()
     */
    private function getSlaComplianceData(array $dateRange, int $currentCompanyId, ?int $requesterId, ?string $department)
    {
        $requests = ServiceRequest::query()
            ->with(['sla', 'subService.service.family.contract', 'requester'])
            ->reportable()
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($requesterId, fn($q) => $q->where('requester_id', $requesterId))
            ->when($department, function ($q) use ($department) {
                $q->whereHas('requester', fn($rq) => $rq->where('department', $department));
            })
            ->get();

        return $requests->groupBy('subService.service.name')->map(function ($serviceRequests, $serviceName) {
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
                'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0,
            ];
        })->values()->sortByDesc('compliance_rate')->values();
    }

    /**
     * Get service performance data (total requests, avg resolution hours, resolved count).
     *
     * Reuses logic from ReportController::servicePerformance()
     */
    private function getServicePerformanceData(array $dateRange, int $currentCompanyId, ?int $requesterId, ?string $department)
    {
        $driver = ServiceRequest::query()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $familyExpr = "CASE WHEN contracts.number IS NULL THEN service_families.name ELSE (contracts.number || ' - ' || service_families.name) END";
            $avgExpr = "AVG((julianday(COALESCE(service_requests.resolved_at, datetime('now'))) - julianday(COALESCE(service_requests.responded_at, service_requests.created_at))) * 24)";
        } else {
            $familyExpr = "CASE WHEN contracts.number IS NULL THEN service_families.name ELSE CONCAT(contracts.number, ' - ', service_families.name) END";
            $avgExpr = "AVG(TIMESTAMPDIFF(HOUR, COALESCE(service_requests.responded_at, service_requests.created_at), COALESCE(service_requests.resolved_at, NOW())))";
        }

        return ServiceRequest::selectRaw("
            services.name as service_name,
            {$familyExpr} as family_name,
            COUNT(service_requests.id) as total_requests,
            {$avgExpr} as avg_resolution_hours,
            COUNT(CASE WHEN service_requests.status = 'RESUELTA' THEN 1 END) as resolved_count
        ")
        ->reportable()
        ->join('sub_services', 'service_requests.sub_service_id', '=', 'sub_services.id')
        ->join('services', 'sub_services.service_id', '=', 'services.id')
        ->join('service_families', 'services.service_family_id', '=', 'service_families.id')
        ->leftJoin('contracts', 'service_families.contract_id', '=', 'contracts.id')
        ->leftJoin('requesters', 'service_requests.requester_id', '=', 'requesters.id')
        ->whereBetween('service_requests.created_at', [$dateRange['start'], $dateRange['end']])
        ->when($currentCompanyId, fn($q) => $q->where('service_requests.company_id', $currentCompanyId))
        ->when($requesterId, fn($q) => $q->where('service_requests.requester_id', $requesterId))
        ->when($department !== null && $department !== '', fn($q) => $q->where('requesters.department', $department))
        ->whereNull('service_requests.deleted_at')
        ->groupBy('services.id', 'services.name', 'service_families.name', 'contracts.number')
        ->get();
    }

    // =========================================================================
    // EXPORT METHODS
    // =========================================================================

    /**
     * Generate PDF export for Services and SLA report.
     */
    private function exportPdf($slaData, $performanceData, array $dateRange, string $timestamp): Response
    {
        $data = compact('slaData', 'performanceData', 'dateRange');

        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('reports.exports.services-sla-pdf', $data);
            return $pdf->download("reporte-servicios-sla-{$timestamp}.pdf");
        }

        $html = view('reports.exports.services-sla-pdf', $data)->render();
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"reporte-servicios-sla-{$timestamp}.pdf\"",
        ]);
    }

    /**
     * Generate CSV export for Services and SLA report.
     */
    private function exportCsv($slaData, $performanceData, array $dateRange, string $timestamp): Response
    {
        $csv = "REPORTE SERVICIOS Y SLA\n";
        $csv .= "Período: " . $dateRange['start']->format('d/m/Y') . " - " . $dateRange['end']->format('d/m/Y') . "\n\n";

        // SLA Compliance section
        $csv .= "=== CUMPLIMIENTO SLA ===\n";
        $csv .= "Servicio,Familia,Total Solicitudes,Cumplidas,Vencidas,Tasa de Cumplimiento (%)\n";
        foreach ($slaData as $item) {
            $csv .= sprintf(
                "\"%s\",\"%s\",%d,%d,%d,%.2f\n",
                $item['service_name'],
                $item['family'],
                $item['total_requests'],
                $item['compliant'],
                $item['overdue'],
                $item['compliance_rate']
            );
        }

        $csv .= "\n=== RENDIMIENTO DE SERVICIOS ===\n";
        $csv .= "Servicio,Familia,Total Solicitudes,Promedio Horas Resolución,Resueltas\n";
        foreach ($performanceData as $item) {
            $csv .= sprintf(
                "\"%s\",\"%s\",%d,%.1f,%d\n",
                $item->service_name,
                $item->family_name,
                $item->total_requests,
                round($item->avg_resolution_hours ?? 0, 1),
                $item->resolved_count
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"reporte-servicios-sla-{$timestamp}.csv\"",
        ]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get date range from request, defaulting to last 30 days.
     */
    private function getDateRange(Request $request): array
    {
        $startInput = $request->input('date_from');
        $endInput = $request->input('date_to');

        $startDate = $startInput
            ? Carbon::parse($startInput)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $endInput
            ? Carbon::parse($endInput)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Format family label with contract number prefix when available.
     */
    private function formatFamilyLabel($family): string
    {
        $familyName = $family?->name ?? 'N/A';
        $contractNumber = $family?->contract?->number;

        return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
    }
}
