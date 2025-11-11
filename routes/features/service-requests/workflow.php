<?php

use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// FLUJO DE TRABAJO DE SOLICITUDES DE SERVICIO
// =============================================================================

Route::prefix('service-requests')
    ->name('service-requests.')
    ->group(function () {
        // ✅ CORREGIDO: Cambiar de POST a PATCH
        Route::patch('/{service_request}/accept', [ServiceRequestController::class, 'accept'])->name('accept');

        // ✅ CORREGIDO: Cambiar de POST a PATCH
        Route::patch('/{service_request}/start', [ServiceRequestController::class, 'start'])->name('start');

        // Mostrar formulario de resolución
        Route::get('/{service_request}/resolve-form', [ServiceRequestController::class, 'showResolveForm'])->name('resolve-form');

        // ✅ MANTENER solo esta ruta para resolver
        Route::patch('/{service_request}/resolve', [ServiceRequestController::class, 'resolve'])->name('resolve');

        // Cerrar solicitud
        Route::post('/{service_request}/close', [ServiceRequestController::class, 'close'])->name('close');

        // Cancelar solicitud
        Route::post('/{service_request}/cancel', [ServiceRequestController::class, 'cancel'])->name('cancel');

        // Pausar solicitud
        Route::post('/{service_request}/pause', [ServiceRequestController::class, 'pause'])->name('pause');

        // Reanudar solicitud
        Route::post('/{service_request}/resume', [ServiceRequestController::class, 'resume'])->name('resume');

        // Línea de tiempo
        Route::get('/{service_request}/timeline', [ServiceRequestController::class, 'showTimeline'])->name('timeline');


        // ✅ TEMPORAL: Sin middleware de permisos
        Route::post('/{service_request}/quick-assign', [ServiceRequestController::class, 'quickAssign'])->name('quick-assign');
        // ->middleware('can:assign-service-requests'); // COMENTADO TEMPORALMENTE
    });
