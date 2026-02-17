<?php
// routes/web.php

use App\Http\Controllers\RequesterManagementController;
use App\Http\Controllers\DepartmentController;

// Gestión de Solicitantes
Route::prefix('requester-management')->name('requester-management.')->middleware(['auth'])->group(function () {
    Route::resource('requesters', RequesterManagementController::class)->except(['create', 'show']);
    Route::get('requesters/create', [RequesterManagementController::class, 'create'])->name('requesters.create');
    Route::get('requesters/{requester}', [RequesterManagementController::class, 'show'])->name('requesters.show');
    Route::patch('requesters/{requester}/toggle-status', [RequesterManagementController::class, 'toggleStatus'])->name('requesters.toggle-status');

    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::patch('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');
});
