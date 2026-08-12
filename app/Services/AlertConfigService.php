<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de configuración de alertas operativas.
 *
 * Proporciona acceso tipado y cacheado a los parámetros de alertas
 * almacenados en system_settings.
 */
class AlertConfigService
{
    /**
     * Tiempo de cache en segundos (5 minutos).
     */
    private const CACHE_TTL = 300;

    /**
     * Prefijo de cache para configuración de alertas.
     */
    private const CACHE_PREFIX = 'alert_config:';

    /**
     * Valores por defecto para cada parámetro.
     */
    private const DEFAULTS = [
        'alert.sla_risk_threshold_percent' => 80,
        'alert.stale_days_p0' => 1,
        'alert.stale_days_p1' => 2,
        'alert.stale_days_p2' => 4,
        'alert.stale_days_p3' => 7,
        'alert.stale_days_p4' => 14,
        'alert.paused_max_days' => 5,
        'alert.pending_acceptance_hours' => 4,
        'alert.high_priority_idle_hours' => 8,
        'alert.blocked_task_days' => 2,
        'alert.schedule_time' => '07:00',
        'alert.auto_resolve_enabled' => 1,
        'alert.system_enabled' => 1,
    ];

    /**
     * Obtener un valor de configuración (cacheado).
     */
    public function get(string $key, $default = null)
    {
        $fallback = $default ?? (self::DEFAULTS[$key] ?? null);

        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            fn () => SystemSetting::get($key, $fallback)
        );
    }

    /**
     * Obtener un valor como entero.
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Obtener un valor como booleano.
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default ? '1' : '0');
    }

    // ==================== ACCESORES TIPADOS ====================

    /**
     * Porcentaje de tiempo SLA consumido para alertar "en riesgo".
     */
    public function slaRiskThresholdPercent(): int
    {
        return $this->getInt('alert.sla_risk_threshold_percent', 80);
    }

    /**
     * Días sin actividad para generar alerta según prioridad.
     */
    public function staleDaysForPriority(string $priority): int
    {
        $key = 'alert.stale_days_' . strtolower($priority);

        return $this->getInt($key, 7);
    }

    /**
     * Días máximo en pausa antes de alertar.
     */
    public function pausedMaxDays(): int
    {
        return $this->getInt('alert.paused_max_days', 5);
    }

    /**
     * Horas sin aceptar una solicitud para generar alerta.
     */
    public function pendingAcceptanceHours(): int
    {
        return $this->getInt('alert.pending_acceptance_hours', 4);
    }

    /**
     * Horas para alertar que una P0/P1 no ha sido iniciada.
     */
    public function highPriorityIdleHours(): int
    {
        return $this->getInt('alert.high_priority_idle_hours', 8);
    }

    /**
     * Días de tarea bloqueada para generar alerta.
     */
    public function blockedTaskDays(): int
    {
        return $this->getInt('alert.blocked_task_days', 2);
    }

    /**
     * Hora de ejecución diaria del motor.
     */
    public function scheduleTime(): string
    {
        return $this->get('alert.schedule_time', '07:00');
    }

    /**
     * Si la resolución automática está habilitada.
     */
    public function autoResolveEnabled(): bool
    {
        return $this->getBool('alert.auto_resolve_enabled', true);
    }

    /**
     * Si el sistema de alertas está activo.
     */
    public function systemEnabled(): bool
    {
        return $this->getBool('alert.system_enabled', true);
    }

    // ==================== GESTIÓN ====================

    /**
     * Actualizar un parámetro y limpiar cache.
     */
    public function set(string $key, $value): void
    {
        SystemSetting::set($key, (string) $value);
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Obtener toda la configuración de alertas como array.
     */
    public function all(): array
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = [
                'value' => $this->get($key, $default),
                'default' => $default,
                'key' => $key,
            ];
        }

        return $settings;
    }

    /**
     * Limpiar toda la cache de configuración de alertas.
     */
    public function clearCache(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Obtener metadatos de configuración para la vista admin.
     */
    public static function getSettingsMetadata(): array
    {
        return [
            'alert.sla_risk_threshold_percent' => [
                'label' => 'Umbral de riesgo SLA (%)',
                'description' => 'Porcentaje de tiempo SLA consumido para generar alerta de riesgo',
                'type' => 'number',
                'min' => 50,
                'max' => 95,
                'unit' => '%',
                'group' => 'sla',
            ],
            'alert.stale_days_p0' => [
                'label' => 'Inactividad P0 (días)',
                'description' => 'Días sin actividad para solicitudes P0 (Atender hoy)',
                'type' => 'number',
                'min' => 1,
                'max' => 7,
                'unit' => 'días',
                'group' => 'inactividad',
            ],
            'alert.stale_days_p1' => [
                'label' => 'Inactividad P1 (días)',
                'description' => 'Días sin actividad para solicitudes P1 (24-48h)',
                'type' => 'number',
                'min' => 1,
                'max' => 10,
                'unit' => 'días',
                'group' => 'inactividad',
            ],
            'alert.stale_days_p2' => [
                'label' => 'Inactividad P2 (días)',
                'description' => 'Días sin actividad para solicitudes P2 (Esta semana)',
                'type' => 'number',
                'min' => 2,
                'max' => 14,
                'unit' => 'días',
                'group' => 'inactividad',
            ],
            'alert.stale_days_p3' => [
                'label' => 'Inactividad P3 (días)',
                'description' => 'Días sin actividad para solicitudes P3 (Cola operativa)',
                'type' => 'number',
                'min' => 3,
                'max' => 30,
                'unit' => 'días',
                'group' => 'inactividad',
            ],
            'alert.stale_days_p4' => [
                'label' => 'Inactividad P4 (días)',
                'description' => 'Días sin actividad para solicitudes P4 (Archivar/validar)',
                'type' => 'number',
                'min' => 7,
                'max' => 60,
                'unit' => 'días',
                'group' => 'inactividad',
            ],
            'alert.paused_max_days' => [
                'label' => 'Máximo en pausa (días)',
                'description' => 'Días máximo que una solicitud puede estar pausada sin alerta',
                'type' => 'number',
                'min' => 1,
                'max' => 30,
                'unit' => 'días',
                'group' => 'estados',
            ],
            'alert.pending_acceptance_hours' => [
                'label' => 'Pendiente de aceptación (horas)',
                'description' => 'Horas sin aceptar una solicitud nueva para generar alerta',
                'type' => 'number',
                'min' => 1,
                'max' => 48,
                'unit' => 'horas',
                'group' => 'estados',
            ],
            'alert.high_priority_idle_hours' => [
                'label' => 'Prioridad alta sin iniciar (horas)',
                'description' => 'Horas para alertar que una solicitud P0/P1 no ha sido iniciada',
                'type' => 'number',
                'min' => 1,
                'max' => 48,
                'unit' => 'horas',
                'group' => 'estados',
            ],
            'alert.blocked_task_days' => [
                'label' => 'Tarea bloqueada (días)',
                'description' => 'Días de tarea bloqueada para generar alerta',
                'type' => 'number',
                'min' => 1,
                'max' => 14,
                'unit' => 'días',
                'group' => 'tareas',
            ],
            'alert.schedule_time' => [
                'label' => 'Hora de evaluación',
                'description' => 'Hora diaria en que el motor de alertas evalúa las solicitudes',
                'type' => 'time',
                'group' => 'sistema',
            ],
            'alert.auto_resolve_enabled' => [
                'label' => 'Resolución automática',
                'description' => 'Resolver alertas automáticamente cuando la condición desaparece',
                'type' => 'boolean',
                'group' => 'sistema',
            ],
            'alert.system_enabled' => [
                'label' => 'Sistema de alertas activo',
                'description' => 'Activar o desactivar el sistema de alertas operativas',
                'type' => 'boolean',
                'group' => 'sistema',
            ],
        ];
    }
}
