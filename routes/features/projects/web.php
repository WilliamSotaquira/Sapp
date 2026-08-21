<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE PROYECTOS
// =============================================================================

Route::middleware(['auth'])->group(function () {
    Route::resource('projects', ProjectController::class);

    // Vincular/desvincular solicitudes
    Route::post('/projects/{project}/link-request', [ProjectController::class, 'linkRequest'])
        ->name('projects.link-request');

    Route::delete('/projects/{project}/unlink-request/{serviceRequest}', [ProjectController::class, 'unlinkRequest'])
        ->name('projects.unlink-request');

    // Informe del proyecto
    Route::get('/projects/{project}/report', [ProjectController::class, 'report'])
        ->name('projects.report');

    Route::get('/projects/{project}/export-report', [ProjectController::class, 'exportReport'])
        ->name('projects.export-report');
});
