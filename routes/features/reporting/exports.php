<?php

use App\Http\Controllers\Reports\ReportController as ReportsController;
use App\Http\Controllers\Reports\TimelineReportController;
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
    // LÍNEA DE TIEMPO - RUTAS CORREGIDAS
    // =========================================================================
    Route::prefix('timeline')->name('timeline.')->group(function () {
        // Listado de solicitudes por rango de fechas
        Route::get('/', [TimelineReportController::class, 'requestTimeline'])->name('index');

        // Detalle de timeline de una solicitud específica
        Route::get('/detail/{id}', [TimelineReportController::class, 'showTimeline'])->name('detail');

        // Exportar timeline de una solicitud específica
        Route::get('/export/{id}/{format}', [TimelineReportController::class, 'exportTimeline'])->name('export');

        // Búsqueda por ticket number - CORREGIDO
        Route::get('/by-ticket', [TimelineReportController::class, 'timelineByTicket'])->name('by-ticket');

        // Manejar acceso GET a download-by-ticket (redireccionar al formulario)
        Route::get('/download-by-ticket', function() {
            return redirect()->route('reports.timeline.by-ticket')
                ->with('info', 'Por favor usa el formulario para buscar y descargar el timeline de una solicitud.');
        });

        // Procesar búsqueda por ticket - CORREGIDO (usar POST)
        Route::post('/download-by-ticket', [TimelineReportController::class, 'downloadTimelineByTicket'])
            ->name('download-by-ticket');
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
