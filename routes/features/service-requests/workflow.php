<?php

use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// FLUJO DE TRABAJO DE SOLICITUDES DE SERVICIO
// =============================================================================

Route::prefix('service-requests')->name('service-requests.')->group(function () {
    // Aceptar solicitud
    Route::post('/{service_request}/accept', [ServiceRequestController::class, 'accept'])
        ->name('accept');

    // Iniciar procesamiento
    Route::post('/{service_request}/start', [ServiceRequestController::class, 'start'])
        ->name('start');

    // Mostrar formulario de resolución
    Route::get('/{service_request}/resolve-form', [ServiceRequestController::class, 'showResolveForm'])
        ->name('resolve-form');

    // Resolver con evidencias
    Route::post('/{service_request}/resolve-with-evidence', [ServiceRequestController::class, 'resolveWithEvidence'])
        ->name('resolve-with-evidence');

    // Resolver (método original)
    Route::post('/{service_request}/resolve', [ServiceRequestController::class, 'resolve'])
        ->name('resolve');

    // Cerrar solicitud
    Route::post('/{service_request}/close', [ServiceRequestController::class, 'close'])
        ->name('close');

    // Cancelar solicitud
    Route::post('/{service_request}/cancel', [ServiceRequestController::class, 'cancel'])
        ->name('cancel');

    // Pausar solicitud
    Route::post('/{service_request}/pause', [ServiceRequestController::class, 'pause'])
        ->name('pause');

    // Reanudar solicitud
    Route::post('/{service_request}/resume', [ServiceRequestController::class, 'resume'])
        ->name('resume');

    // Línea de tiempo
    Route::get('/{service_request}/timeline', [ServiceRequestController::class, 'showTimeline'])
        ->name('timeline');
});
