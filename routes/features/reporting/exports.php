<?php

use App\Http\Controllers\Reports\ReportController as ReportsController;
use App\Http\Controllers\Reports\TimeRangeReportController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// EXPORTACIONES DE REPORTES
// =============================================================================

Route::prefix('reports')->name('reports.')->group(function () {

    // =========================================================================
    // EXPORTACIONES PRINCIPALES
    // =========================================================================
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/{reportType}/pdf', [ReportsController::class, 'exportPdf'])->name('pdf');
        Route::get('/{reportType}/excel', [ReportsController::class, 'exportExcel'])->name('excel');

        // Nuevas rutas para reporte de resumen
        Route::post('/summary-pdf', [ReportsController::class, 'exportSummaryPDF'])->name('summary-pdf');
        Route::post('/summary-excel', [ReportsController::class, 'exportSummaryExcel'])->name('summary-excel');
    });

    // =========================================================================
    // LÍNEA DE TIEMPO - Migrada a UnifiedTimelineController en web.php
    // Se mantiene solo la ruta de download-by-ticket para compatibilidad
    // =========================================================================
    Route::prefix('timeline')->name('timeline.')->group(function () {
        // Manejar acceso GET a download-by-ticket (redireccionar al formulario)
        Route::get('/download-by-ticket', function() {
            return redirect()->route('reports.timeline.index')
                ->with('info', 'Por favor usa el formulario para buscar y descargar el timeline de una solicitud.');
        });

        // Procesar búsqueda por ticket - legacy route, redirect to new search
        Route::post('/download-by-ticket', function(\Illuminate\Http\Request $request) {
            return redirect()->route('reports.timeline.index')
                ->with('info', 'Use la nueva funcionalidad de búsqueda en la página de Línea de Tiempo.');
        })->name('download-by-ticket');

        // Legacy route: by-ticket redirects to unified timeline
        Route::get('/by-ticket', function() {
            return redirect()->route('reports.timeline.index');
        })->name('by-ticket');
    });

    // =========================================================================
    // REPORTE POR RANGO DE TIEMPO - NUEVA FUNCIONALIDAD
    // =========================================================================
    Route::prefix('time-range')->name('time-range.')->group(function () {
        // Mostrar formulario del reporte
        Route::get('/', [TimeRangeReportController::class, 'index'])->name('index');

        // Generar reporte (PDF, Excel o ZIP con evidencias)
        Route::post('/generate', [TimeRangeReportController::class, 'generate'])->name('generate');
    });
});
