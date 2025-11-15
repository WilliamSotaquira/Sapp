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

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Estadísticas generales para el dashboard de reportes
        $stats = [
            'total_requests' => ServiceRequest::count(),
            'pending_requests' => ServiceRequest::where('status', 'PENDIENTE')->count(),
            'resolved_requests' => ServiceRequest::where('status', 'RESUELTA')->count(),
            'overdue_requests' => ServiceRequest::whereNotNull('resolution_deadline')
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
            ->with(['sla', 'subService.service'])
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

            return [
                'service_name' => $serviceName,
                'family' => $serviceRequests->first()->subService->service->family->name ?? 'N/A',
                'total_requests' => $total,
                'compliant' => $compliant,
                'overdue' => $overdue,
                'non_compliant' => $overdue, // Alias para la vista
                'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0,
                'sla_name' => $serviceRequests->first()->sla->name ?? 'N/A'
            ];
        })->values()->sortByDesc('compliance_rate');

        $services = Service::with('family')->get();

        if ($request->has('export')) {
            return Excel::download(new SlaComplianceExport($dateFrom, $dateTo),
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
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('status')
        ->get();

        // Calcular total de solicitudes para la vista
        $totalRequests = $statusData->sum('count');

        // Convertir a formato esperado por la vista (indexado por status)
        $requestsByStatus = $statusData->keyBy('status');

        if ($request->has('export')) {
            return Excel::download(new RequestsByStatusExport($dateFrom, $dateTo),
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
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('criticality_level')
        ->get();

        // Transformar datos en array asociativo indexado por nivel de criticidad
        $criticalityData = $rawData->keyBy('criticality_level');

        if ($request->has('export')) {
            return Excel::download(new CriticalityLevelsExport($dateFrom, $dateTo),
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
            service_families.name as family_name,
            COUNT(service_requests.id) as total_requests,
            AVG(TIMESTAMPDIFF(HOUR, service_requests.created_at, COALESCE(service_requests.resolved_at, NOW()))) as avg_resolution_hours,
            COUNT(CASE WHEN service_requests.status = 'RESUELTA' THEN 1 END) as resolved_count
        ")
        ->join('sub_services', 'service_requests.sub_service_id', '=', 'sub_services.id')
        ->join('services', 'sub_services.service_id', '=', 'services.id')
        ->join('service_families', 'services.service_family_id', '=', 'service_families.id')
        ->whereBetween('service_requests.created_at', [$dateFrom, $dateTo])
        ->whereNull('service_requests.deleted_at')
        ->groupBy('services.id', 'services.name', 'service_families.name')
        ->get();

        if ($request->has('export')) {
            return Excel::download(new ServicePerformanceExport($dateFrom, $dateTo),
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
        ->where('created_at', '>=', now()->subMonths($months))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return view('reports.monthly-trends', compact('trendsData', 'months'));
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
}
