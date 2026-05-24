<?php

use App\Http\Controllers\Reports\ReportController as ReportsController;
use App\Http\Controllers\Reports\CutAnalyticsReportController;
use App\Http\Controllers\Reports\CutController;
use App\Http\Controllers\Reports\UnifiedTimelineController;
use App\Http\Controllers\Reports\ServicesSlaController;
use App\Http\Controllers\Reports\OperationalOverviewController;
use App\Http\Controllers\Reports\SearchAnalysisController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// MÓDULO DE REPORTES
// =============================================================================

Route::prefix('reports')->name('reports.')->group(function () {
    // Dashboard de reportes
    Route::get('/', [ReportsController::class, 'index'])->name('index');

    // =========================================================================
    // Línea de Tiempo unificada (reemplaza timeline.index y timeline.by-ticket)
    // =========================================================================
    Route::prefix('timeline')->name('timeline.')->group(function () {
        Route::get('/', [UnifiedTimelineController::class, 'index'])->name('index');
        Route::get('/detail/{id}', [UnifiedTimelineController::class, 'show'])->name('show');
        Route::post('/search', [UnifiedTimelineController::class, 'searchByTicket'])->name('search');
        Route::get('/export/{id}/{format}', [UnifiedTimelineController::class, 'export'])->name('export');
    });

    // =========================================================================
    // Servicios y SLA (reemplaza sla-compliance y service-performance)
    // =========================================================================
    Route::prefix('services-sla')->name('services-sla.')->group(function () {
        Route::get('/', [ServicesSlaController::class, 'index'])->name('index');
        Route::get('/export/{format}', [ServicesSlaController::class, 'export'])->name('export');
    });

    // =========================================================================
    // Panorama Operativo (reemplaza requests-by-status, criticality-levels, monthly-trends)
    // =========================================================================
    Route::prefix('operational-overview')->name('operational-overview.')->group(function () {
        Route::get('/', [OperationalOverviewController::class, 'index'])->name('index');
        Route::get('/export/{format}', [OperationalOverviewController::class, 'export'])->name('export');
    });

    // =========================================================================
    // Búsqueda y Análisis (nuevo)
    // =========================================================================
    Route::prefix('search-analysis')->name('search-analysis.')->group(function () {
        Route::get('/', [SearchAnalysisController::class, 'index'])->name('index');
        Route::get('/search', [SearchAnalysisController::class, 'search'])->name('search');
        Route::get('/export/{format}', [SearchAnalysisController::class, 'export'])->name('export');
    });

    // =========================================================================
    // Deprecated routes - redirect to new unified URLs
    // =========================================================================
    Route::get('/sla-compliance', function () {
        return redirect()->route('reports.services-sla.index');
    })->name('sla-compliance');

    Route::get('/requests-by-status', function () {
        return redirect()->route('reports.operational-overview.index');
    })->name('requests-by-status');

    Route::get('/criticality-levels', function () {
        return redirect()->route('reports.operational-overview.index');
    })->name('criticality-levels');

    Route::get('/service-performance', function () {
        return redirect()->route('reports.services-sla.index');
    })->name('service-performance');

    Route::get('/monthly-trends', function () {
        return redirect()->route('reports.operational-overview.index');
    })->name('monthly-trends');

    // Generación de reportes
    Route::prefix('generate')->name('generate.')->group(function () {
        Route::post('/summary', [ReportsController::class, 'generateSummary'])->name('summary');
    });

    // =========================================================================
    // Cortes (periodos) - agrupar solicitudes por actividad (sin cambios)
    // =========================================================================
    Route::prefix('cuts')->name('cuts.')->group(function () {
        Route::get('/', [CutController::class, 'index'])->name('index');
        Route::get('/create', [CutController::class, 'create'])->name('create');
        Route::get('/{cut}/edit', [CutController::class, 'edit'])->name('edit');
        Route::post('/', [CutController::class, 'store'])->name('store');
        Route::put('/{cut}', [CutController::class, 'update'])->name('update');
        Route::get('/{cut}', [CutController::class, 'show'])->name('show');

        // Consulta y recalculo de solicitudes asociadas por fecha de creación
        Route::get('/{cut}/requests', [CutController::class, 'requests'])->name('requests');
        Route::get('/{cut}/associated-requests', [CutController::class, 'associatedRequests'])->name('associated-requests');
        Route::post('/{cut}/requests', [CutController::class, 'updateRequests'])->name('requests.update');
        Route::post('/{cut}/requests/add-ticket', [CutController::class, 'addRequestByTicket'])->name('requests.add-ticket');
        Route::delete('/{cut}/requests/{service_request}', [CutController::class, 'removeRequest'])->name('requests.remove');

        Route::post('/{cut}/sync', [CutController::class, 'sync'])->name('sync');
        Route::get('/{cut}/export', [CutController::class, 'export'])->name('export');
        Route::get('/{cut}/pdf', [CutController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{cut}/analytics', [CutAnalyticsReportController::class, 'show'])->name('analytics');
        Route::get('/{cut}/analytics/export/csv', [CutAnalyticsReportController::class, 'exportCsv'])->name('analytics.export.csv');
        Route::get('/{cut}/analytics/export/pdf', [CutAnalyticsReportController::class, 'exportPdf'])->name('analytics.export.pdf');
    });

    // Ruta de prueba (puedes eliminarla en producción)
    Route::get('/test-evidence-relation', function () {
        try {
            $request = \App\Models\ServiceRequest::with('evidences.uploadedBy')->first();

            if (!$request) {
                return "No hay ServiceRequests en la base de datos";
            }

            $evidenceCount = $request->evidences->count();
            $evidenceWithUser = $request->evidences->first();

            return [
                'service_request' => $request->ticket_number,
                'evidences_count' => $evidenceCount,
                'first_evidence' => $evidenceWithUser ? [
                    'file_name' => $evidenceWithUser->file_name,
                    'uploaded_by' => $evidenceWithUser->uploadedBy ? $evidenceWithUser->uploadedBy->name : 'No user'
                ] : 'No evidences'
            ];
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    });
});
