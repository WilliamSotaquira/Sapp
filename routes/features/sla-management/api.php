<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// API - GESTIÓN DE SLAs
// =============================================================================

// Agregar APIs específicas de SLAs aquí...
// Estas rutas estarán disponibles bajo /api/...

Route::prefix('slas')->name('slas.')->group(function () {
    // Ejemplo: API para validación de SLAs
    // Route::post('/validate', [SLAController::class, 'validateSLA'])->name('validate');
});
