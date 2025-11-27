<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    /**
     * Exportar reporte de cumplimiento SLA a PDF
     */
    public function exportSlaCompliancePdf(Request $request)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d');

            $slaCompliance = $this->getSlaComplianceData($dateRange);
            $pdf = Pdf::loadView('reports.exports.sla-compliance-pdf', compact('slaCompliance', 'dateRange'));

            return $pdf->download("reporte-cumplimiento-sla-{$timestamp}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Exportar reporte de estados a PDF
     */
    public function exportRequestsByStatusPdf(Request $request)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d');

            $data = $this->getRequestsByStatusData($dateRange);
            $totalRequests = $data->sum('count');

            $pdf = Pdf::loadView('reports.exports.requests-by-status-pdf', [
                'requestsByStatus' => $data,
                'totalRequests' => $totalRequests,
                'dateRange' => $dateRange
            ]);

            return $pdf->download("reporte-estados-solicitudes-{$timestamp}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Exportar reporte de niveles de criticidad a PDF
     */
    public function exportCriticalityLevelsPdf(Request $request)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d');

            $criticalityData = $this->getCriticalityLevelsData($dateRange);
            $pdf = Pdf::loadView('reports.exports.criticality-levels-pdf', compact('criticalityData', 'dateRange'));

            return $pdf->download("reporte-criticidad-{$timestamp}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Métodos auxiliares para obtener datos
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

    private function getSlaComplianceData($dateRange)
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

    private function getRequestsByStatusData($dateRange)
    {
        return ServiceRequest::reportable()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }

    private function getCriticalityLevelsData($dateRange)
    {
        return ServiceRequest::reportable()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get();
    }

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
}
