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

        // Rechazar solicitud
        Route::post('/{service_request}/reject', [ServiceRequestController::class, 'reject'])->name('reject');

        // ✅ CORREGIDO: Cambiar de POST a PATCH
        Route::patch('/{service_request}/start', [ServiceRequestController::class, 'start'])->name('start');

        // Mostrar formulario de resolución
        Route::get('/{service_request}/resolve-form', [ServiceRequestController::class, 'showResolveForm'])->name('resolve-form');

        // ✅ MANTENER solo esta ruta para resolver
        Route::patch('/{service_request}/resolve', [ServiceRequestController::class, 'resolve'])->name('resolve');

        //Reasignar solicitud
        Route::get('/{service_request}/reassign', [ServiceRequestController::class, 'reassign'])->name('reassign');

        // Enviar reasignación
        Route::post('/{service_request}/reassign-submit', [ServiceRequestController::class, 'reassignSubmit'])->name('reassign-submit');

        // Pausar solicitud
        Route::post('/{service_request}/pause', [ServiceRequestController::class, 'pause'])->name('pause');

        // Reanudar solicitud
        Route::post('/{service_request}/resume', [ServiceRequestController::class, 'resume'])->name('resume');

        // Cerrar solicitud
        Route::post('/{service_request}/close', [ServiceRequestController::class, 'close'])->name('close');

        // Reabrir solicitud
        Route::post('/{service_request}/reopen', [ServiceRequestController::class, 'reopen'])->name('reopen');

        // Cancelar solicitud
        Route::post('/{service_request}/cancel', [ServiceRequestController::class, 'cancel'])->name('cancel');

        // Línea de tiempo
        Route::get('/{service_request}/timeline', [ServiceRequestController::class, 'showTimeline'])->name('timeline');

        // En tu archivo workflow.php, agregar si necesitas:

        // ✅ TEMPORAL: Sin middleware de permisos
        Route::post('/{service_request}/quick-assign', [ServiceRequestController::class, 'quickAssign'])->name('quick-assign');
        // ->middleware('can:assign-service-requests'); // COMENTADO TEMPORALMENTE

        // Mostrar formulario de reasignación
        Route::get('/service-requests/{service_request}/reassign', [ServiceRequestController::class, 'reassign'])->name('service-requests.reassign');
    });
