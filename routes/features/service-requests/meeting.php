<?php

use App\Http\Controllers\MeetingRequestController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// REUNIONES Y SOLICITUDES DERIVADAS
// =============================================================================

Route::prefix('service-requests')
    ->name('service-requests.')
    ->group(function () {
        // -----------------------------------------------------------------
        // Meeting sub-routes: service-requests/{service_request}/meeting/...
        // -----------------------------------------------------------------
        Route::prefix('{service_request}/meeting')
            ->name('meeting.')
            ->group(function () {
                // Actualizar detalles de la reunión (fecha, hora, duración, ubicación)
                Route::post('/update-details', [MeetingRequestController::class, 'updateDetails'])
                    ->name('update-details');

                // Agregar participante
                Route::post('/participants', [MeetingRequestController::class, 'addParticipant'])
                    ->name('participants.store');

                // Eliminar participante
                Route::delete('/participants/{participant}', [MeetingRequestController::class, 'removeParticipant'])
                    ->name('participants.destroy');

                // Registrar asistencia
                Route::post('/attendance', [MeetingRequestController::class, 'markAttendance'])
                    ->name('attendance');

                // Crear compromiso
                Route::post('/commitments', [MeetingRequestController::class, 'storeCommitment'])
                    ->name('commitments.store');
            });

        // -----------------------------------------------------------------
        // Derived request creation route
        // -----------------------------------------------------------------
        Route::get('/{service_request}/derive', [ServiceRequestController::class, 'create'])
            ->name('derive');
    });
