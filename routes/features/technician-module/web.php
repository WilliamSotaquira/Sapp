<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TechnicianScheduleController;

/*
|--------------------------------------------------------------------------
| Módulo de Tiempos y Capacidad para Técnicos
|--------------------------------------------------------------------------
|
| Gestión integral de técnicos, tareas, calendario y capacidad
| Para técnicos de soporte TI y desarrollo web
|
*/

// =============================================================================
// GESTIÓN DE TÉCNICOS
// =============================================================================

Route::prefix('technicians')->name('technicians.')->group(function () {

    // CRUD de técnicos
    Route::get('/', [TechnicianController::class, 'index'])->name('index');
    Route::get('/create', [TechnicianController::class, 'create'])->name('create');
    Route::post('/', [TechnicianController::class, 'store'])->name('store');
    Route::get('/{technician}', [TechnicianController::class, 'show'])->name('show');
    Route::get('/{technician}/edit', [TechnicianController::class, 'edit'])->name('edit');
    Route::put('/{technician}', [TechnicianController::class, 'update'])->name('update');
    Route::delete('/{technician}', [TechnicianController::class, 'destroy'])->name('destroy');

    // Toggle rol de administrador
    Route::patch('/{technician}/toggle-admin', [TechnicianController::class, 'toggleAdmin'])->name('toggle-admin');

    // Gestión de skills
    Route::get('/{technician}/skills', [TechnicianController::class, 'skills'])->name('skills');
    Route::post('/{technician}/skills', [TechnicianController::class, 'addSkill'])->name('skills.add');

    // Dashboard de capacidad
    Route::get('/{technician}/capacity', [TechnicianController::class, 'capacity'])->name('capacity');
});

// =============================================================================
// GESTIÓN DE TAREAS
// =============================================================================

Route::prefix('tasks')->name('tasks.')->group(function () {

    // CRUD de tareas
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::get('/create', [TaskController::class, 'create'])->name('create');
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::get('/{task}', [TaskController::class, 'show'])->name('show');
    Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
    Route::put('/{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');

    // Asignación de tareas
    Route::post('/{task}/assign', [TaskController::class, 'assign'])->name('assign');
    Route::get('/{task}/suggest-assignment', [TaskController::class, 'suggestAssignment'])->name('suggest-assignment');

    // Workflow de tareas
    Route::post('/{task}/start', [TaskController::class, 'start'])->name('start');
    Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('complete');
    Route::post('/{task}/block', [TaskController::class, 'block'])->name('block');
    Route::post('/{task}/unblock', [TaskController::class, 'unblock'])->name('unblock');
    Route::post('/{task}/reschedule', [TaskController::class, 'reschedule'])->name('reschedule');
    Route::post('/{task}/update-duration', [TaskController::class, 'updateDuration'])->name('update-duration');
});

// =============================================================================
// CALENDARIO Y AGENDA
// =============================================================================

Route::prefix('technician-schedule')->name('technician-schedule.')->group(function () {

    // Calendario principal
    Route::get('/', [TechnicianScheduleController::class, 'index'])->name('index');

    // Mi agenda (vista del técnico)
    Route::get('/my-agenda', [TechnicianScheduleController::class, 'myAgenda'])->name('my-agenda');

    // Capacidad del equipo
    Route::get('/team-capacity', [TechnicianScheduleController::class, 'teamCapacity'])->name('team-capacity');

    // API para calendario
    Route::get('/events', [TechnicianScheduleController::class, 'getEvents'])->name('events');
    Route::post('/tasks/{task}/move', [TechnicianScheduleController::class, 'moveTask'])->name('move-task');
});
