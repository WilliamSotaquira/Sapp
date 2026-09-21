<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('evidence:clean-backups')->daily();

Schedule::command('cuts:health-check')->dailyAt('07:00');

// Motor de alertas operativas: evaluar solicitudes y tareas activas.
// La hora configurable se lee de system_settings, pero SOLO si la tabla ya
// existe. Consultar la BD directamente al registrar las rutas de consola rompe
// el arranque del framework cuando la tabla aún no existe: migraciones frescas
// y el bootstrap de tests con RefreshDatabase cargan el ConsoleKernel antes de
// crear las tablas. Ante cualquier duda se usa el valor por defecto (07:00).
$alertScheduleTime = '07:00';
try {
    if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
        $alertScheduleTime = \App\Models\SystemSetting::get('alert.schedule_time', '07:00');
    }
} catch (\Throwable $e) {
    // BD no disponible durante el arranque (p. ej. sin conexión): default seguro.
}

Schedule::command('alerts:generate')->dailyAt($alertScheduleTime)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/alerts.log'));

// Archivar solicitudes sin actividad por más de 90 días (semanal, lunes 6:00)
Schedule::command('service-requests:archive-stale --force')->weeklyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/archive-stale.log'));
