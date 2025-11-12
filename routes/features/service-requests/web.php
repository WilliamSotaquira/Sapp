<?php

use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE SOLICITUDES DE SERVICIO - CRUD
// =============================================================================

Route::resource('service-requests', ServiceRequestController::class);

Route::get('/service-requests/{service_request}/edit', [ServiceRequestController::class, 'edit'])->name('service-requests.edit');

//  Agrega ruta para descargar reporte PDF individual
Route::get('/service-requests/{service_request}/download-report', [ServiceRequestController::class, 'downloadReport'])->name('service-requests.download-report');

// Agrega esta ruta para subir evidencias
Route::post('/service-requests/{service_request}/evidences', [ServiceRequestEvidenceController::class, 'store'])->name('service-requests.evidences.store');

// Ruta para cerrar solicitud por vencimiento
Route::post('/service-requests/{service_request}/close-vencimiento', [ServiceRequestController::class, 'closeByVencimiento'])->name('service-requests.close-vencimiento');
