<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// API - REPORTES Y ANALYTICS
// =============================================================================

// Agregar APIs específicas de reportes aquí...
// Estas rutas estarán disponibles bajo /api/...

Route::prefix('reports')->name('reports.')->group(function () {
    // Ejemplo: API para datos en tiempo real de reportes
    // Route::get('/live-data', [ReportController::class, 'liveData'])->name('live-data');
});
