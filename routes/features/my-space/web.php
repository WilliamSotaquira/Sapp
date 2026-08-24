<?php

use App\Http\Controllers\MySpaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mi Espacio — Centro de Trabajo Personal
|--------------------------------------------------------------------------
|
| Rutas para el módulo "Mi Espacio". Este módulo es cross-workspace:
| no requiere tener un workspace seleccionado. Centraliza tareas,
| alertas, recordatorios y calendario personal del usuario.
|
*/

Route::prefix('inicio')->name('my-space.')->middleware(['auth'])->group(function () {

    // Vista principal: Mi Día
    Route::get('/', [MySpaceController::class, 'index'])->name('index');

    // Vista directa a reuniones (redirige con tab activo)
    Route::get('/meetings', function () {
        return redirect()->route('my-space.index', ['tab' => 'meetings']);
    })->name('meetings');

    // API para refresh dinámico (stats actualizados)
    Route::get('/api/refresh', [MySpaceController::class, 'refresh'])->name('api.refresh');

    // Acciones rápidas sobre tareas
    Route::post('/tasks/{task}/complete', [MySpaceController::class, 'completeTask'])->name('tasks.complete');
    Route::post('/tasks/{task}/start', [MySpaceController::class, 'startTask'])->name('tasks.start');
});
