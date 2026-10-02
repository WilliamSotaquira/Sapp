<?php

use App\Http\Controllers\OperationalAlertController;
use App\Http\Controllers\PerformanceMetricsController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// ALERTAS OPERATIVAS
// =============================================================================

Route::prefix('operational-alerts')->name('operational-alerts.')->middleware(['auth'])->group(function () {
    // -------------------------------------------------------------------------
    // Panel y gestión de alertas (administrativo) — §6.5
    // -------------------------------------------------------------------------
    Route::middleware('role:admin')->group(function () {
        // Panel principal
        Route::get('/', [OperationalAlertController::class, 'index'])->name('index');

        // Acciones sobre alertas individuales
        Route::post('/{alert}/mark-read', [OperationalAlertController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{alert}/dismiss', [OperationalAlertController::class, 'dismiss'])->name('dismiss');
        Route::post('/{alert}/resolve', [OperationalAlertController::class, 'resolve'])->name('resolve');

        // Acciones masivas
        Route::post('/mark-all-read', [OperationalAlertController::class, 'markAllAsRead'])->name('mark-all-read');

        // Recordatorios
        Route::post('/reminder', [OperationalAlertController::class, 'createReminder'])->name('reminder.store');
        Route::get('/reminders', [OperationalAlertController::class, 'reminders'])->name('reminders');
        Route::delete('/reminder/{alert}', [OperationalAlertController::class, 'destroyReminder'])->name('reminder.destroy');
    });

    // -------------------------------------------------------------------------
    // APIs de badge del layout compartido (solo auth; scopeadas por usuario en
    // el controlador). Blindarlas con role:admin provocaría 403 en cada carga
    // de página para un técnico. — §6.5
    // -------------------------------------------------------------------------
    Route::get('/api/unread-count', [OperationalAlertController::class, 'unreadCount'])->name('api.unread-count');
    Route::get('/api/recent', [OperationalAlertController::class, 'recent'])->name('api.recent');
});

// =============================================================================
// INDICADORES DE RENDIMIENTO (administrativo)
// =============================================================================

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/performance-metrics', [PerformanceMetricsController::class, 'index'])->name('performance-metrics.index');
});
