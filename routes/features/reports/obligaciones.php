<?php

use App\Http\Controllers\ObligacionesReportController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas para Reporte de Obligaciones
 */
Route::prefix('obligaciones')->name('obligaciones.')->group(function () {
    Route::get('/', [ObligacionesReportController::class, 'index'])->name('index');
    Route::get('/export', [ObligacionesReportController::class, 'export'])->name('export');
});
