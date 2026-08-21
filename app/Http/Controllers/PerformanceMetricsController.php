<?php

namespace App\Http\Controllers;

use App\Services\PerformanceMetricsService;
use Illuminate\Http\Request;

class PerformanceMetricsController extends Controller
{
    public function __construct(private PerformanceMetricsService $metricsService)
    {
    }

    /**
     * Vista de indicadores de rendimiento operativo.
     */
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = in_array($days, [7, 14, 30, 60, 90]) ? $days : 30;

        $companyId = (int) session('current_company_id') ?: null;

        $metrics = $this->metricsService->getDashboardMetrics($companyId, $days);

        return view('operational-alerts.metrics', compact('metrics', 'days'));
    }
}
