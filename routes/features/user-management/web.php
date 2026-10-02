<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// GESTIÓN DE USUARIOS Y PERFILES
// =============================================================================

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// CRUD de usuarios (administrativo)
Route::middleware('role:admin')->group(function () {
    Route::resource('users', UserManagementController::class)->except(['show']);
});
