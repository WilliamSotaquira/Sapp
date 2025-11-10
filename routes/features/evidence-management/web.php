<?php

use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ServiceRequestEvidenceController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE EVIDENCIAS INDEPENDIENTES
// =============================================================================

Route::middleware(['auth'])->group(function () {
    Route::delete('/evidences/{evidence}', [EvidenceController::class, 'destroy'])
        ->name('evidences.destroy')
        ->middleware('can:delete,evidence');

    Route::get('/evidences/{evidence}/download', [EvidenceController::class, 'download'])
        ->name('evidences.download')
        ->middleware('can:view,evidence');
});

// =============================================================================
// EVIDENCIAS DE SOLICITUDES DE SERVICIO
// =============================================================================

Route::prefix('service-requests/{service_request}/evidences')->name('service-requests.evidences.')->group(function () {
    // Crear evidencia
    Route::post('/', [ServiceRequestEvidenceController::class, 'store'])
        ->name('store');

    // Mostrar formulario de creación
    Route::get('/create', [ServiceRequestEvidenceController::class, 'create'])
        ->name('create');

    // Mostrar evidencia específica
    Route::get('/{evidence}', [ServiceRequestEvidenceController::class, 'show'])
        ->name('show');

    // Editar evidencia
    Route::get('/{evidence}/edit', [ServiceRequestEvidenceController::class, 'edit'])
        ->name('edit');

    // Actualizar evidencia
    Route::put('/{evidence}', [ServiceRequestEvidenceController::class, 'update'])
        ->name('update');

    // Eliminar evidencia
    Route::delete('/{evidence}', [ServiceRequestEvidenceController::class, 'destroy'])
        ->name('destroy');

    // Descargar archivo de evidencia
    Route::get('/{evidence}/download', [ServiceRequestEvidenceController::class, 'download'])
        ->name('download');
});
