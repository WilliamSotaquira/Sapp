<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class AlertSettingsSeeder extends Seeder
{
    /**
     * Seed de configuración de umbrales para el sistema de alertas operativas.
     *
     * Convenciones de claves:
     * - alert.* → parámetros del motor de alertas
     * - alert.sla_* → umbrales relacionados con SLA
     * - alert.stale_* → umbrales de inactividad por nivel de prioridad
     * - alert.schedule_* → configuración del scheduler
     */
    public function run(): void
    {
        $settings = [
            // === SLA ===
            // Porcentaje de tiempo SLA consumido para generar alerta "en riesgo"
            'alert.sla_risk_threshold_percent' => '80',

            // === INACTIVIDAD (días sin actividad por prioridad) ===
            'alert.stale_days_p0' => '1',
            'alert.stale_days_p1' => '2',
            'alert.stale_days_p2' => '4',
            'alert.stale_days_p3' => '7',
            'alert.stale_days_p4' => '14',

            // === PAUSAS ===
            // Días máximo en pausa antes de alertar
            'alert.paused_max_days' => '5',

            // === ACEPTACIÓN ===
            // Horas sin aceptar una solicitud para generar alerta
            'alert.pending_acceptance_hours' => '4',

            // === PRIORIDAD ALTA SIN INICIAR ===
            // Horas para alertar que una P0/P1 no ha sido iniciada
            'alert.high_priority_idle_hours' => '8',

            // === TAREAS BLOQUEADAS ===
            // Días de tarea bloqueada para generar alerta
            'alert.blocked_task_days' => '2',

            // === SCHEDULER ===
            // Hora de ejecución diaria del motor de alertas (formato 24h)
            'alert.schedule_time' => '07:00',

            // === RESOLUCIÓN AUTOMÁTICA ===
            // Si una alerta se resuelve automáticamente cuando la condición desaparece
            'alert.auto_resolve_enabled' => '1',

            // === GENERAL ===
            // Si el sistema de alertas está activo
            'alert.system_enabled' => '1',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('Configuración de alertas operativas sembrada exitosamente.');
        $this->command->info('Total: ' . count($settings) . ' parámetros configurados.');
    }
}
