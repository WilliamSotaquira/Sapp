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
