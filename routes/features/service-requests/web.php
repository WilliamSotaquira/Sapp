<?php

use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE SOLICITUDES DE SERVICIO - CRUD
// =============================================================================

Route::resource('service-requests', ServiceRequestController::class);
