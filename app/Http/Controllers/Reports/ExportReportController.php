<?php

namespace App\Http\Controllers\Reports;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportReportController extends ReportController
{
    /**
     * Export reports to PDF
     */
    public function exportPdf(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            // Instanciar los controllers necesarios
            $slaController = new SlaReportController();
            $statusController = new StatusReportController();
            $criticalityController = new CriticalityReportController();
            $performanceController = new PerformanceReportController();

            switch ($reportType) {
                case 'sla-compliance':
                    $slaCompliance = $slaController->getSlaComplianceData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.sla-compliance-pdf',
                        compact('slaCompliance', 'dateRange'),
                        "reporte-cumplimiento-sla-{$timestamp}.pdf"
                    );

                case 'requests-by-status':
                    $data = $statusController->getRequestsByStatusData($dateRange);
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
                    $criticalityData = $criticalityController->getCriticalityLevelsData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.criticality-levels-pdf',
                        compact('criticalityData', 'dateRange'),
                        "reporte-criticidad-{$timestamp}.pdf"
                    );

                case 'service-performance':
                    $servicePerformance = $performanceController->getServicePerformanceData($dateRange);
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

            // Instanciar los controllers necesarios
            $slaController = new SlaReportController();
            $statusController = new StatusReportController();
            $criticalityController = new CriticalityReportController();
            $performanceController = new PerformanceReportController();

            switch ($reportType) {
                case 'sla-compliance':
                    $data = $slaController->getSlaComplianceData($dateRange);
                    $csv = $slaController->formatSlaComplianceForCsv($data);
                    return $this->downloadCsv($csv, "reporte-cumplimiento-sla-{$timestamp}.csv");

                case 'requests-by-status':
                    $data = $statusController->getRequestsByStatusData($dateRange);
                    $csv = $statusController->formatRequestsByStatusForCsv($data);
                    return $this->downloadCsv($csv, "reporte-estados-solicitudes-{$timestamp}.csv");

                case 'criticality-levels':
                    $data = $criticalityController->getCriticalityLevelsData($dateRange);
                    $csv = $criticalityController->formatCriticalityLevelsForCsv($data);
                    return $this->downloadCsv($csv, "reporte-criticidad-{$timestamp}.csv");

                case 'service-performance':
                    $data = $performanceController->getServicePerformanceData($dateRange);
                    $csv = $performanceController->formatServicePerformanceForCsv($data);
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
     * Método auxiliar para descargar PDF
     */
    private function downloadPdf($view, $data, $filename)
    {
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
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
}
