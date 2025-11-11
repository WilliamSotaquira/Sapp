<?php

use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE SOLICITUDES DE SERVICIO - CRUD
// =============================================================================

Route::resource('service-requests', ServiceRequestController::class);

Route::get('/service-requests/{service_request}/edit', [ServiceRequestController::class, 'edit'])->name('service-requests.edit');

// ✅ AGREGAR: Ruta para descargar reporte PDF individual
Route::get('/service-requests/{service_request}/download-report', [ServiceRequestController::class, 'downloadReport'])->name('service-requests.download-report');
