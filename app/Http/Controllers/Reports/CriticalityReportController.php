<?php

namespace App\Http\Controllers\Reports;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class CriticalityReportController extends ReportController
{
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
     * Obtener datos para Criticality Levels
     */
    public function getCriticalityLevelsData($dateRange)
    {
        return ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get();
    }

    /**
     * Formatear datos para CSV - Criticality
     */
    public function formatCriticalityLevelsForCsv($data)
    {
        $total = $data->sum('count');
        $csv = "Nivel de Criticidad,Cantidad,Porcentaje\n";
        foreach ($data as $item) {
            $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
            $csv .= "\"{$item->criticality_level}\",{$item->count},{$percentage}\n";
        }
        return $csv;
    }
}
