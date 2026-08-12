<?php

use App\Http\Controllers\AlertSettingsController;
use App\Http\Controllers\SystemSettingsController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// CONFIGURACIÓN DEL SISTEMA
// =============================================================================

Route::prefix('settings')->name('settings.')->middleware(['auth'])->group(function () {
    Route::get('/', [SystemSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [SystemSettingsController::class, 'update'])->name('update');

    // Configuración de alertas operativas
    Route::get('/alerts', [AlertSettingsController::class, 'edit'])->name('alerts.edit');
    Route::put('/alerts', [AlertSettingsController::class, 'update'])->name('alerts.update');
});
