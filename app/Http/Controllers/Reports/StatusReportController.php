<?php

namespace App\Http\Controllers\Reports;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class StatusReportController extends ReportController
{
    /**
     * Service Requests by Status Report
     */
    public function requestsByStatus(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $requestsByStatus = ServiceRequest::reportable()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalRequests = $requestsByStatus->sum('count');

        return view('reports.requests-by-status', compact('requestsByStatus', 'totalRequests', 'dateRange'));
    }

    /**
     * Obtener datos para Requests by Status
     */
    public function getRequestsByStatusData($dateRange)
    {
        return ServiceRequest::reportable()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }

    /**
     * Formatear datos para CSV - Status
     */
    public function formatRequestsByStatusForCsv($data)
    {
        $total = $data->sum('count');
        $csv = "Estado,Cantidad,Porcentaje\n";
        foreach ($data as $item) {
            $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
            $csv .= "\"{$item->status}\",{$item->count},{$percentage}\n";
        }
        return $csv;
    }
}
