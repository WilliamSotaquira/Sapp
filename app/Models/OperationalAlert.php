<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alertable_type',
        'alertable_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'metadata',
        'is_read',
        'is_dismissed',
        'is_resolved',
        'alert_at',
        'read_at',
        'dismissed_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'is_resolved' => 'boolean',
        'alert_at' => 'datetime',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ==================== CONSTANTES ====================

    // Tipos de alerta
    const TYPE_SLA_AT_RISK = 'sla_at_risk';
    const TYPE_SLA_BREACHED = 'sla_breached';
    const TYPE_STALE_REQUEST = 'stale_request';
    const TYPE_HIGH_PRIORITY_IDLE = 'high_priority_idle';
    const TYPE_PAUSED_TOO_LONG = 'paused_too_long';
    const TYPE_OVERDUE_RESOLUTION = 'overdue_resolution';
    const TYPE_PENDING_ACCEPTANCE = 'pending_acceptance';
    const TYPE_BLOCKED_TASK = 'blocked_task';
    const TYPE_REMINDER = 'reminder';

    // Severidades
    const SEVERITY_CRITICAL = 'critica';
    const SEVERITY_HIGH = 'alta';
    const SEVERITY_MEDIUM = 'media';
    const SEVERITY_LOW = 'baja';

    /**
     * Definición de tipos con etiquetas, iconos y colores.
     */
    public static array $alertTypes = [
        self::TYPE_SLA_AT_RISK => [
            'label' => 'SLA en riesgo',
            'icon' => 'fa-exclamation-triangle',
            'color' => 'amber',
            'description' => 'El tiempo de SLA está próximo a agotarse',
        ],
        self::TYPE_SLA_BREACHED => [
            'label' => 'SLA incumplido',
            'icon' => 'fa-times-circle',
            'color' => 'red',
            'description' => 'Se ha superado el tiempo establecido en el SLA',
        ],
        self::TYPE_STALE_REQUEST => [
            'label' => 'Solicitud estancada',
            'icon' => 'fa-hourglass-half',
            'color' => 'orange',
            'description' => 'La solicitud no registra actividad en el período establecido',
        ],
        self::TYPE_HIGH_PRIORITY_IDLE => [
            'label' => 'Alta prioridad sin atender',
            'icon' => 'fa-fire',
            'color' => 'red',
            'description' => 'Una solicitud de alta prioridad no ha sido iniciada',
        ],
        self::TYPE_PAUSED_TOO_LONG => [
            'label' => 'Pausada demasiado tiempo',
            'icon' => 'fa-pause-circle',
            'color' => 'yellow',
            'description' => 'La solicitud lleva demasiado tiempo en pausa',
        ],
        self::TYPE_OVERDUE_RESOLUTION => [
            'label' => 'Resolución vencida',
            'icon' => 'fa-calendar-times',
            'color' => 'red',
            'description' => 'La fecha de resolución ha sido superada',
        ],
        self::TYPE_PENDING_ACCEPTANCE => [
            'label' => 'Pendiente de aceptación',
            'icon' => 'fa-clock',
            'color' => 'blue',
            'description' => 'La solicitud no ha sido aceptada en el tiempo esperado',
        ],
        self::TYPE_BLOCKED_TASK => [
            'label' => 'Tarea bloqueada',
            'icon' => 'fa-ban',
            'color' => 'orange',
            'description' => 'Una tarea lleva demasiado tiempo bloqueada',
        ],
        self::TYPE_REMINDER => [
            'label' => 'Recordatorio',
            'icon' => 'fa-bell',
            'color' => 'blue',
            'description' => 'Recordatorio manual programado',
        ],
    ];

    /**
     * Severidades con etiquetas y colores.
     */
    public static array $severities = [
        self::SEVERITY_CRITICAL => ['label' => 'Crítica', 'color' => 'red', 'order' => 1],
        self::SEVERITY_HIGH => ['label' => 'Alta', 'color' => 'orange', 'order' => 2],
        self::SEVERITY_MEDIUM => ['label' => 'Media', 'color' => 'yellow', 'order' => 3],
        self::SEVERITY_LOW => ['label' => 'Baja', 'color' => 'blue', 'order' => 4],
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación polimórfica (ServiceRequest o Task).
     */
    public function alertable()
    {
        return $this->morphTo();
    }

    /**
     * Usuario que resolvió la alerta.
     */
    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_resolved', false)->where('is_dismissed', false);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false)->where('is_dismissed', false)->where('alert_at', '<=', now());
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeHighOrAbove($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH]);
    }

    public function scopeForServiceRequests($query)
    {
        return $query->where('alertable_type', ServiceRequest::class);
    }

    public function scopeForTasks($query)
    {
        return $query->where('alertable_type', Task::class);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('alert_at', '>=', now()->subDays($days));
    }

    // ==================== MÉTODOS ====================

    /**
     * Marcar como leída.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Descartar la alerta (no requiere acción).
     */
    public function dismiss(): void
    {
        $this->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Resolver la alerta (se atendió la situación).
     */
    public function resolve(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Verificar si ya existe una alerta activa del mismo tipo para el mismo recurso.
     */
    public static function existsActiveFor(string $alertableType, int $alertableId, string $alertType): bool
    {
        return static::where('alertable_type', $alertableType)
            ->where('alertable_id', $alertableId)
            ->where('alert_type', $alertType)
            ->active()
            ->exists();
    }

    /**
     * Crear una alerta solo si no existe una activa del mismo tipo para el recurso.
     */
    public static function createIfNotExists(array $data): ?self
    {
        $alertableType = $data['alertable_type'];
        $alertableId = $data['alertable_id'];
        $alertType = $data['alert_type'];

        if (static::existsActiveFor($alertableType, $alertableId, $alertType)) {
            return null;
        }

        return static::create($data);
    }

    /**
     * Resolver automáticamente alertas cuando la condición ya no aplica.
     */
    public static function autoResolve(string $alertableType, int $alertableId, string $alertType): int
    {
        return static::where('alertable_type', $alertableType)
            ->where('alertable_id', $alertableId)
            ->where('alert_type', $alertType)
            ->active()
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Resuelta automáticamente: la condición ya no aplica.',
            ]);
    }

    // ==================== ACCESSORS ====================

    public function getAlertTypeInfoAttribute(): array
    {
        return static::$alertTypes[$this->alert_type] ?? [
            'label' => $this->alert_type,
            'icon' => 'fa-bell',
            'color' => 'gray',
            'description' => '',
        ];
    }

    public function getSeverityInfoAttribute(): array
    {
        return static::$severities[$this->severity] ?? [
            'label' => $this->severity,
            'color' => 'gray',
            'order' => 99,
        ];
    }

    public function getSeverityColorAttribute(): string
    {
        return $this->severity_info['color'];
    }

    public function getIconAttribute(): string
    {
        return $this->alert_type_info['icon'];
    }

    public function getLabelAttribute(): string
    {
        return $this->alert_type_info['label'];
    }
}
