<?php

use App\Http\Controllers\Reports\ReportController as ReportsController;
use App\Http\Controllers\Reports\TimelineReportController;
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

        // Procesar búsqueda por ticket - CORREGIDO (usar POST)
        Route::post('/download-by-ticket', [TimelineReportController::class, 'downloadTimelineByTicket'])
            ->name('download-by-ticket');
    });
});
