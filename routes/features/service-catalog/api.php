<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// API - CATÁLOGO DE SERVICIOS
// =============================================================================

// Agregar APIs específicas del catálogo de servicios aquí...
// Estas rutas estarán disponibles bajo /api/...

Route::prefix('catalog')->name('catalog.')->group(function () {
    // Ejemplo: API para búsqueda de servicios
    // Route::get('/services/search', [ServiceController::class, 'search'])->name('services.search');
});
