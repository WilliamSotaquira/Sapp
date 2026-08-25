<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('evidence:clean-backups')->daily();

Schedule::command('cuts:health-check')->dailyAt('07:00');

// Motor de alertas operativas: evaluar solicitudes y tareas activas
Schedule::command('alerts:generate')->dailyAt(
    \App\Models\SystemSetting::get('alert.schedule_time', '07:00')
)->withoutOverlapping()->appendOutputTo(storage_path('logs/alerts.log'));

// Archivar solicitudes sin actividad por más de 90 días (semanal, lunes 6:00)
Schedule::command('service-requests:archive-stale --force')->weeklyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/archive-stale.log'));
