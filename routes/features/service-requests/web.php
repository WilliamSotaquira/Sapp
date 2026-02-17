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

// Sugerencias de solicitantes (autocompletado filtros)
Route::get('/service-requests/suggest-requesters', [ServiceRequestController::class, 'suggestRequesters'])->name('service-requests.suggest-requesters');

// Actualizar corte asociado (AJAX)
Route::post('/service-requests/{service_request}/update-cut', [ServiceRequestController::class, 'updateCut'])->name('service-requests.update-cut');

// Filtros guardados por usuario (persistentes)
Route::get('/service-requests/saved-filters', [ServiceRequestController::class, 'savedFiltersIndex'])->name('service-requests.saved-filters.index');
Route::post('/service-requests/saved-filters', [ServiceRequestController::class, 'savedFiltersStore'])->name('service-requests.saved-filters.store');
Route::delete('/service-requests/saved-filters/{savedFilter}', [ServiceRequestController::class, 'savedFiltersDestroy'])->name('service-requests.saved-filters.destroy');
