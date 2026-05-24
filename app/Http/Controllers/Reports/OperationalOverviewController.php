<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OperationalOverviewController extends Controller
{
    /**
     * Allowed values for the months parameter in trends section.
     */
    private const ALLOWED_MONTHS = [3, 6, 12, 24];

    /**
     * Display the unified Operational Overview report.
     * Combines: status distribution, criticality distribution, and monthly trends.
     */
    public function index(Request $request): View
    {
        $dateRange = $this->getDateRange($request);
        $months = $this->getMonths($request);
        $currentCompanyId = (int) session('current_company_id');

        // Status distribution: name, count, percentage (2 decimal places)
        $statusData = $this->getStatusDistribution($dateRange, $currentCompanyId);

        // Criticality distribution: level, count, avg resolution hours (1 decimal place)
        $criticalityData = $this->getCriticalityDistribution($dateRange, $currentCompanyId);

        // Monthly trends: total, resolved, completion rate, avg resolution hours
        $trendsData = $this->getMonthlyTrends($months, $currentCompanyId);

        $allowedMonths = self::ALLOWED_MONTHS;

        return view('reports.operational-overview.index', compact(
            'statusData',
            'criticalityData',
            'trendsData',
            'dateRange',
            'months',
            'allowedMonths'
        ));
    }

    /**
     * Export the Operational Overview report in the specified format.
     */
    public function export(Request $request, string $format): Response
    {
        $dateRange = $this->getDateRange($request);
        $months = $this->getMonths($request);
        $currentCompanyId = (int) session('current_company_id');

        $statusData = $this->getStatusDistribution($dateRange, $currentCompanyId);
        $criticalityData = $this->getCriticalityDistribution($dateRange, $currentCompanyId);
        $trendsData = $this->getMonthlyTrends($months, $currentCompanyId);
        $allowedMonths = self::ALLOWED_MONTHS;

        $timestamp = now()->format('Y-m-d_His');

        try {
            if ($format === 'pdf') {
                return $this->exportPdf($statusData, $criticalityData, $trendsData, $dateRange, $months, $timestamp);
            }

            if ($format === 'csv') {
                return $this->exportCsv($statusData, $criticalityData, $trendsData, $dateRange, $months, $timestamp);
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
     * Get status distribution data.
     * Returns: status name, count, percentage (rounded to 2 decimal places).
     */
    private function getStatusDistribution(array $dateRange, int $currentCompanyId)
    {
        return ServiceRequest::selectRaw("
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
        ")
        ->reportable()
        ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->groupBy('status')
        ->get();
    }

    /**
     * Get criticality distribution data.
     * Returns: criticality level, count, avg resolution hours (rounded to 1 decimal place).
     */
    private function getCriticalityDistribution(array $dateRange, int $currentCompanyId)
    {
        $driver = ServiceRequest::query()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $avgExpr = "ROUND(AVG((julianday(COALESCE(resolved_at, datetime('now'))) - julianday(COALESCE(responded_at, created_at))) * 24), 1)";
        } else {
            $avgExpr = "ROUND(AVG(TIMESTAMPDIFF(HOUR, COALESCE(responded_at, created_at), COALESCE(resolved_at, NOW()))), 1)";
        }

        return ServiceRequest::selectRaw("
            criticality_level,
            COUNT(*) as count,
            {$avgExpr} as avg_resolution_hours
        ")
        ->reportable()
        ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->groupBy('criticality_level')
        ->get();
    }

    /**
     * Get monthly trends data.
     * Returns: month, total requests, resolved requests, completion rate, avg resolution hours.
     */
    private function getMonthlyTrends(int $months, int $currentCompanyId)
    {
        $driver = ServiceRequest::query()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $monthExpr = "strftime('%Y-%m', created_at)";
            $avgExpr = "AVG((julianday(COALESCE(resolved_at, datetime('now'))) - julianday(COALESCE(responded_at, created_at))) * 24)";
        } else {
            $monthExpr = "DATE_FORMAT(created_at, '%Y-%m')";
            $avgExpr = "AVG(TIMESTAMPDIFF(HOUR, COALESCE(responded_at, created_at), COALESCE(resolved_at, NOW())))";
        }

        $rawData = ServiceRequest::selectRaw("
            {$monthExpr} as month,
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status IN ('RESUELTA', 'CERRADA') THEN 1 END) as resolved_requests,
            {$avgExpr} as avg_resolution_hours
        ")
        ->reportable()
        ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
        ->where('created_at', '>=', now()->subMonths($months))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return $rawData->map(function ($item) {
            $completionRate = $item->total_requests > 0
                ? round(($item->resolved_requests / $item->total_requests) * 100, 2)
                : 0;

            $monthName = Carbon::createFromFormat('Y-m', $item->month)->locale('es')->format('M Y');

            return [
                'month' => $item->month,
                'month_name' => $monthName,
                'total_requests' => $item->total_requests,
                'resolved_requests' => $item->resolved_requests,
                'completion_rate' => $completionRate,
                'avg_resolution_hours' => round($item->avg_resolution_hours ?? 0, 1),
            ];
        });
    }

    // =========================================================================
    // EXPORT METHODS
    // =========================================================================

    /**
     * Generate PDF export.
     */
    private function exportPdf($statusData, $criticalityData, $trendsData, $dateRange, $months, $timestamp): Response
    {
        $data = compact('statusData', 'criticalityData', 'trendsData', 'dateRange', 'months');

        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('reports.exports.operational-overview-pdf', $data);
            return $pdf->download("reporte-panorama-operativo-{$timestamp}.pdf");
        }

        $html = view('reports.exports.operational-overview-pdf', $data)->render();
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"reporte-panorama-operativo-{$timestamp}.pdf\"",
        ]);
    }

    /**
     * Generate CSV export.
     */
    private function exportCsv($statusData, $criticalityData, $trendsData, $dateRange, $months, $timestamp): Response
    {
        $csv = "PANORAMA OPERATIVO\n";
        $csv .= "Período: " . $dateRange['start']->format('d/m/Y') . " - " . $dateRange['end']->format('d/m/Y') . "\n\n";

        // Status distribution section
        $csv .= "=== DISTRIBUCIÓN POR ESTADO ===\n";
        $csv .= "Estado,Cantidad,Porcentaje (%)\n";
        foreach ($statusData as $item) {
            $csv .= sprintf(
                "\"%s\",%d,%.2f\n",
                $item->status,
                $item->count,
                $item->percentage
            );
        }

        // Criticality distribution section
        $csv .= "\n=== DISTRIBUCIÓN POR CRITICIDAD ===\n";
        $csv .= "Nivel de Criticidad,Cantidad,Promedio Horas Resolución\n";
        foreach ($criticalityData as $item) {
            $csv .= sprintf(
                "\"%s\",%d,%.1f\n",
                $item->criticality_level,
                $item->count,
                $item->avg_resolution_hours ?? 0
            );
        }

        // Monthly trends section
        $csv .= "\n=== TENDENCIAS MENSUALES (últimos {$months} meses) ===\n";
        $csv .= "Mes,Total Solicitudes,Resueltas,Tasa de Completitud (%),Promedio Horas Resolución\n";
        foreach ($trendsData as $item) {
            $csv .= sprintf(
                "\"%s\",%d,%d,%.2f,%.1f\n",
                $item['month_name'],
                $item['total_requests'],
                $item['resolved_requests'],
                $item['completion_rate'],
                $item['avg_resolution_hours']
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"reporte-panorama-operativo-{$timestamp}.csv\"",
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

        // Swap if start is after end
        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Get validated months parameter for trends section.
     * Only accepts 3, 6, 12, or 24. Defaults to 12.
     */
    private function getMonths(Request $request): int
    {
        $months = (int) $request->input('months', 12);

        if (!in_array($months, self::ALLOWED_MONTHS, true)) {
            return 12;
        }

        return $months;
    }
}
