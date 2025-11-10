<?php

use App\Http\Controllers\SLAController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE SLAs
// =============================================================================

Route::resource('slas', SLAController::class);

// Crear SLA desde el modal en solicitudes de servicio
Route::post('/slas/create-from-modal', [SLAController::class, 'storeFromModal'])
    ->name('slas.create-from-modal');
