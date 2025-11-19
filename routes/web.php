<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicTrackingController;

// =============================================================================
// RUTAS PÚBLICAS
// =============================================================================

Route::get('/', function () {
    return view('welcome');
});

// Consulta pública de solicitudes (sin autenticación)
Route::prefix('consultar')->name('public.tracking.')->group(function () {
    Route::get('/', [PublicTrackingController::class, 'index'])->name('index');
    Route::post('/search', [PublicTrackingController::class, 'search'])->name('search');
    Route::get('/{ticketNumber}', [PublicTrackingController::class, 'show'])->name('show');
});

// =============================================================================
// RUTAS AUTENTICADAS
// =============================================================================

Route::middleware('auth')->group(function () {

    // =========================================================================
    // DASHBOARD PRINCIPAL
    // =========================================================================

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('verified')->name('dashboard');

    // =========================================================================
    // FEATURES DEL SISTEMA
    // =========================================================================

    // Catálogo de servicios
    require __DIR__ . '/features/service-catalog/web.php';

    // Gestión de solicitudes de servicio
    require __DIR__ . '/features/service-requests/web.php';
    require __DIR__ . '/features/service-requests/workflow.php';

    // Gestión de SLAs
    require __DIR__ . '/features/sla-management/web.php';

    // Gestión de evidencias
    require __DIR__ . '/features/evidence-management/web.php';

    // Reportes y analytics
    require __DIR__ . '/features/reporting/web.php';
    require __DIR__ . '/features/reporting/exports.php';

    // Gestión de usuarios
    require __DIR__ . '/features/user-management/web.php';

    // Gestión de solicitantes
    require __DIR__ . '/features/requester-management/web.php';

    // Módulo de Tiempos y Capacidad para Técnicos
    require __DIR__ . '/features/technician-module/web.php';

    // Tareas Predefinidas
    Route::resource('standard-tasks', App\Http\Controllers\StandardTaskController::class);

    // Rutas para toggle de tareas y subtareas
    Route::post('tasks/{task}/toggle-status', [App\Http\Controllers\TaskController::class, 'toggleStatus'])->name('tasks.toggle-status');
    Route::post('tasks/{task}/subtasks/{subtask}/toggle', [App\Http\Controllers\TaskController::class, 'toggleSubtask'])->name('tasks.subtasks.toggle');

    // =========================================================================
    // APIS PARA FORMULARIOS WEB
    // =========================================================================
    require __DIR__ . '/web-api.php';

    // =========================================================================
    // REQUIREMENTS
    // =========================================================================
    require __DIR__ . '/requirements.php';
});

// =============================================================================
// ARCHIVOS EXTERNOS
// =============================================================================

require __DIR__ . '/auth.php';
