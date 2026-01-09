<?php

use App\Http\Controllers\Reports\ReportController as ReportsController;
use App\Http\Controllers\Reports\CutController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// MÓDULO DE REPORTES - CORREGIDO
// =============================================================================

Route::prefix('reports')->name('reports.')->group(function () {
    // Dashboard de reportes
    Route::get('/', [ReportsController::class, 'index'])->name('index');

    // Reportes de análisis
    Route::get('/sla-compliance', [ReportsController::class, 'slaCompliance'])->name('sla-compliance');
    Route::get('/requests-by-status', [ReportsController::class, 'requestsByStatus'])->name('requests-by-status');
    Route::get('/criticality-levels', [ReportsController::class, 'criticalityLevels'])->name('criticality-levels');
    Route::get('/service-performance', [ReportsController::class, 'servicePerformance'])->name('service-performance');
    Route::get('/monthly-trends', [ReportsController::class, 'monthlyTrends'])->name('monthly-trends');

    // Generación de reportes
    Route::prefix('generate')->name('generate.')->group(function () {
        Route::post('/summary', [ReportsController::class, 'generateSummary'])->name('summary');
    });

    // Cortes (periodos) - agrupar solicitudes por actividad
    Route::prefix('cuts')->name('cuts.')->group(function () {
        Route::get('/', [CutController::class, 'index'])->name('index');
        Route::get('/create', [CutController::class, 'create'])->name('create');
        Route::post('/', [CutController::class, 'store'])->name('store');
        Route::get('/{cut}', [CutController::class, 'show'])->name('show');

        // Gestión manual de solicitudes asociadas
        Route::get('/{cut}/requests', [CutController::class, 'requests'])->name('requests');
        Route::post('/{cut}/requests', [CutController::class, 'updateRequests'])->name('requests.update');
        Route::post('/{cut}/requests/add-ticket', [CutController::class, 'addRequestByTicket'])->name('requests.add-ticket');
        Route::delete('/{cut}/requests/{service_request}', [CutController::class, 'removeRequest'])->name('requests.remove');

        Route::post('/{cut}/sync', [CutController::class, 'sync'])->name('sync');
        Route::get('/{cut}/pdf', [CutController::class, 'exportPdf'])->name('export-pdf');
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
