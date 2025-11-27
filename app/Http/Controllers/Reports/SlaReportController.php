<?php

namespace App\Http\Controllers\Reports;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class SlaReportController extends ReportController
{
    /**
     * SLA Compliance Report
     */
    public function slaCompliance(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $slaCompliance = ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->reportable()
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
     * Obtener datos para SLA Compliance
     */
    public function getSlaComplianceData($dateRange)
    {
        return ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->reportable()
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
}
