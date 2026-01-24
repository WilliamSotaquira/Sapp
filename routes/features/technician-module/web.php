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
    Route::post('/{task}/unschedule', [TaskController::class, 'unschedule'])->name('unschedule');
    Route::post('/{task}/schedule-quick', [TaskController::class, 'scheduleQuick'])->name('schedule-quick');
    Route::post('/{task}/clear-schedule', [TaskController::class, 'clearSchedule'])->name('clear-schedule');

    // Subtareas
    Route::post('/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('subtasks.store');
    Route::put('/{task}/subtasks/{subtask}', [TaskController::class, 'updateSubtask'])->name('subtasks.update');
    Route::delete('/{task}/subtasks/{subtask}', [TaskController::class, 'destroySubtask'])->name('subtasks.destroy');
    Route::post('/{task}/subtasks/{subtask}/toggle', [TaskController::class, 'toggleSubtaskStatus'])->name('subtasks.toggle');

    // Checklists
    Route::post('/{task}/checklists', [TaskController::class, 'storeChecklist'])->name('checklists.store');
    Route::put('/{task}/checklists/{checklist}', [TaskController::class, 'updateChecklist'])->name('checklists.update');
    Route::delete('/{task}/checklists/{checklist}', [TaskController::class, 'destroyChecklist'])->name('checklists.destroy');
    Route::post('/{task}/checklists/{checklist}/toggle', [TaskController::class, 'toggleChecklist'])->name('checklists.toggle');
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

    // Vista Gantt multi-técnico
    Route::get('/gantt', [TechnicianScheduleController::class, 'ganttView'])->name('gantt');

    // API para calendario
    Route::get('/events', [TechnicianScheduleController::class, 'getEvents'])->name('events');
    Route::post('/tasks/{task}/move', [TechnicianScheduleController::class, 'moveTask'])->name('move-task');

    // Bloqueos de horario
    Route::post('/blocks', [TechnicianScheduleController::class, 'storeBlock'])->name('store-block');
    Route::delete('/blocks/{block}', [TechnicianScheduleController::class, 'destroyBlock'])->name('destroy-block');
});

// Alertas de tareas
Route::prefix('task-alerts')->name('task-alerts.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TaskAlertController::class, 'index'])->name('index');
    Route::post('/{alert}/read', [\App\Http\Controllers\TaskAlertController::class, 'markAsRead'])->name('read');
    Route::post('/{alert}/dismiss', [\App\Http\Controllers\TaskAlertController::class, 'dismiss'])->name('dismiss');
    Route::post('/mark-all-read', [\App\Http\Controllers\TaskAlertController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::get('/unread-count', [\App\Http\Controllers\TaskAlertController::class, 'getUnreadCount'])->name('unread-count');
    Route::post('/generate', [\App\Http\Controllers\TaskAlertController::class, 'generate'])->name('generate');
});
