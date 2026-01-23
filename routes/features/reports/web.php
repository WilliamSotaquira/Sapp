<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas de Reportes
 */
Route::prefix('reports')->name('reports.')->middleware(['auth', 'verified'])->group(function () {
    // Reportes de Obligaciones
    require __DIR__ . '/obligaciones.php';
});
