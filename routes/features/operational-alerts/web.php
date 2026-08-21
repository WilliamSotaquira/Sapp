<?php

use App\Http\Controllers\OperationalAlertController;
use App\Http\Controllers\PerformanceMetricsController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// ALERTAS OPERATIVAS
// =============================================================================

Route::prefix('operational-alerts')->name('operational-alerts.')->middleware(['auth'])->group(function () {
    // Panel principal
    Route::get('/', [OperationalAlertController::class, 'index'])->name('index');

    // Acciones sobre alertas individuales
    Route::post('/{alert}/mark-read', [OperationalAlertController::class, 'markAsRead'])->name('mark-read');
    Route::post('/{alert}/dismiss', [OperationalAlertController::class, 'dismiss'])->name('dismiss');
    Route::post('/{alert}/resolve', [OperationalAlertController::class, 'resolve'])->name('resolve');

    // Acciones masivas
    Route::post('/mark-all-read', [OperationalAlertController::class, 'markAllAsRead'])->name('mark-all-read');

    // API para badge de navegación
    Route::get('/api/unread-count', [OperationalAlertController::class, 'unreadCount'])->name('api.unread-count');
    Route::get('/api/recent', [OperationalAlertController::class, 'recent'])->name('api.recent');
});

// =============================================================================
// INDICADORES DE RENDIMIENTO
// =============================================================================

Route::middleware(['auth'])->group(function () {
    Route::get('/performance-metrics', [PerformanceMetricsController::class, 'index'])->name('performance-metrics.index');
});
