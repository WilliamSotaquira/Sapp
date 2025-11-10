<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// API - GESTIÓN DE USUARIOS
// =============================================================================

// Agregar APIs específicas de usuarios aquí...
// Estas rutas estarán disponibles bajo /api/...

Route::prefix('users')->name('users.')->group(function () {
    // Ejemplo: API para gestión de perfiles de usuario
    // Route::get('/profile/{user}', [ProfileController::class, 'apiShow'])->name('profile.show');
});
