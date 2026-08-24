<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\PublicTrackingController;
use App\Services\OpenRouterService;

// =============================================================================
// RUTAS PÚBLICAS
// =============================================================================

Route::get('/', function () {
    return view('welcome');
});

// Consulta pública de solicitudes (sin autenticación)
Route::prefix('consultar')
    ->name('public.tracking.')
    ->middleware('throttle:30,1')
    ->group(function () {
        Route::get('/', [PublicTrackingController::class, 'index'])->name('index');
        Route::post('/search', [PublicTrackingController::class, 'search'])->name('search');
        Route::get('/{ticketNumber}', [PublicTrackingController::class, 'show'])->name('show');
    });

// =============================================================================
// RUTAS AUTENTICADAS
// =============================================================================

Route::middleware('auth')->group(function () {
    // Selección de espacio de trabajo
    Route::get('/workspaces/select', [App\Http\Controllers\WorkspaceController::class, 'select'])
        ->name('workspaces.select');

    Route::post('/workspaces/switch', [App\Http\Controllers\WorkspaceController::class, 'switch'])
        ->name('workspaces.switch');

    // =========================================================================
    // DASHBOARD PRINCIPAL (redirige a Mi Espacio)
    // =========================================================================

    Route::get('/dashboard', function () {
        return redirect()->route('my-space.index');
    })->middleware('verified')->name('dashboard');

    // =========================================================================
    // MI ESPACIO — Centro de Trabajo Personal (cross-workspace)
    // =========================================================================

    require __DIR__ . '/features/my-space/web.php';

    // =========================================================================
    // FEATURES DEL SISTEMA
    // =========================================================================

    // Catálogo de servicios
    require __DIR__ . '/features/service-catalog/web.php';

    // Gestión de solicitudes de servicio
    require __DIR__ . '/features/service-requests/web.php';
    require __DIR__ . '/features/service-requests/workflow.php';
    require __DIR__ . '/features/service-requests/meeting.php';

    // Gestión de SLAs
    require __DIR__ . '/features/sla-management/web.php';

    // Gestión de evidencias
    require __DIR__ . '/features/evidence-management/web.php';

    // Reportes y analytics
    require __DIR__ . '/features/reporting/web.php';
    require __DIR__ . '/features/reporting/exports.php';

    // Reportes especializados
    require __DIR__ . '/features/reports/web.php';

    // Gestión de usuarios
    require __DIR__ . '/features/user-management/web.php';

    // Gestión de solicitantes
    require __DIR__ . '/features/requester-management/web.php';

    // Configuración del sistema
    require __DIR__ . '/features/settings/web.php';

    // Alertas operativas
    require __DIR__ . '/features/operational-alerts/web.php';

    // Gestión de proyectos
    require __DIR__ . '/features/projects/web.php';

    // Módulo de Tiempos y Capacidad para Técnicos
    require __DIR__ . '/features/technician-module/web.php';

    // Tareas Predefinidas
    Route::resource('standard-tasks', App\Http\Controllers\StandardTaskController::class);

    // Rutas para toggle de tareas y subtareas
    Route::post('tasks/{task}/toggle-status', [App\Http\Controllers\TaskController::class, 'toggleStatus'])
        ->name('tasks.toggle-status');

    Route::post('tasks/{task}/subtasks/{subtask}/toggle', [App\Http\Controllers\TaskController::class, 'toggleSubtask'])
        ->name('tasks.subtasks.toggle');

    // =========================================================================
    // APIS PARA FORMULARIOS WEB
    // =========================================================================

    require __DIR__ . '/web-api.php';

    // =========================================================================
    // REQUIREMENTS
    // =========================================================================

    require __DIR__ . '/requirements.php';

    // =========================================================================
    // CHAT CON IA (OpenRouter + DeepSeek)
    // =========================================================================

    Route::post('/chat-openrouter', function (Request $request, OpenRouterService $openRouter) {
        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $respuesta = $openRouter->chat($request->message);

        return response()->json([
            'respuesta' => $respuesta
        ]);
    })->name('chat.openrouter');
});

// =============================================================================
// ARCHIVOS EXTERNOS
// =============================================================================

require __DIR__ . '/auth.php';
