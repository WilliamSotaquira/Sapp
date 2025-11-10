<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// API - GESTIÓN DE EVIDENCIAS
// =============================================================================

// Agregar APIs específicas de evidencias aquí...
// Estas rutas estarán disponibles bajo /api/...

Route::prefix('evidences')->name('evidences.')->group(function () {
    // Ejemplo: API para subida múltiple de evidencias
    // Route::post('/bulk-upload', [EvidenceController::class, 'bulkUpload'])->name('bulk-upload');
});
