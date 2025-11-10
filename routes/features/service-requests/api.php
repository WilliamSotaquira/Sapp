<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// API - GESTIÓN DE SOLICITUDES DE SERVICIO
// =============================================================================

// Agregar APIs específicas de solicitudes de servicio aquí...
// Estas rutas estarán disponibles bajo /api/...

Route::prefix('service-requests')->name('service-requests.')->group(function () {
    // Ejemplo: API para estadísticas de solicitudes
    // Route::get('/stats', [ServiceRequestController::class, 'stats'])->name('stats');
});
