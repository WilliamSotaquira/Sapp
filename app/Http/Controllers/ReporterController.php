<?php

namespace App\Http\Controllers;

use App\Models\Reporter;
use Illuminate\Http\Request;

class ReporterController extends Controller
{
    public function index()
    {
        $reporters = Reporter::withCount('requirements')->orderBy('name')->paginate(10);
        return view('reporters.index', compact('reporters'));
    }

    public function create()
    {
        return view('reporters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:reporters',
            'department' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        Reporter::create($validated);

        return redirect()->route('reporters.index')
            ->with('success', 'Reportador creado exitosamente.');
    }

    public function show(Reporter $reporter)
    {
        $reporter->load(['requirements' => function($query) {
            $query->with(['classification', 'project'])
                  ->orderBy('created_at', 'desc');
        }]);

        $requirements = $reporter->requirements()->paginate(10);

        return view('reporters.show', compact('reporter', 'requirements'));
    }

    public function edit(Reporter $reporter)
    {
        return view('reporters.edit', compact('reporter'));
    }

    public function update(Request $request, Reporter $reporter)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:reporters,email,' . $reporter->id,
            'department' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $reporter->update($validated);

        return redirect()->route('reporters.index')
            ->with('success', 'Reportador actualizado exitosamente.');
    }

    public function destroy(Reporter $reporter)
    {
        // Verificar que no tenga requerimientos asociados
        if ($reporter->requirements()->exists()) {
            return redirect()->route('reporters.index')
                ->with('error', 'No se puede eliminar el reportador porque tiene requerimientos asociados.');
        }

        $reporter->delete();

        return redirect()->route('reporters.index')
            ->with('success', 'Reportador eliminado exitosamente.');
    }

     /**
     * Export reports to PDF - Versión REAL con DomPDF
     */
    public function exportPdf(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d');

            switch ($reportType) {
                case 'sla-compliance':
                    $slaCompliance = $this->getSlaComplianceData($dateRange);
                    $pdf = Pdf::loadView('reports.exports.sla-compliance-pdf', compact('slaCompliance', 'dateRange'));
                    return $pdf->download("reporte-cumplimiento-sla-{$timestamp}.pdf");

                case 'requests-by-status':
                    $data = $this->getRequestsByStatusData($dateRange);
                    $totalRequests = $data->sum('count');
                    $pdf = Pdf::loadView('reports.exports.requests-by-status-pdf', [
                        'requestsByStatus' => $data,
                        'totalRequests' => $totalRequests,
                        'dateRange' => $dateRange
                    ]);
                    return $pdf->download("reporte-estados-solicitudes-{$timestamp}.pdf");

                case 'criticality-levels':
                    $criticalityData = $this->getCriticalityLevelsData($dateRange);
                    $pdf = Pdf::loadView('reports.exports.criticality-levels-pdf', compact('criticalityData', 'dateRange'));
                    return $pdf->download("reporte-criticidad-{$timestamp}.pdf");

                case 'service-performance':
                    $servicePerformance = $this->getServicePerformanceData($dateRange);
                    $pdf = Pdf::loadView('reports.exports.service-performance-pdf', compact('servicePerformance', 'dateRange'));
                    return $pdf->download("reporte-rendimiento-servicios-{$timestamp}.pdf");

                case 'monthly-trends':
                    $trends = $this->getMonthlyTrendsData();
                    $pdf = Pdf::loadView('reports.exports.monthly-trends-pdf', compact('trends'));
                    return $pdf->download("reporte-tendencias-mensuales-{$timestamp}.pdf");

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }
}
