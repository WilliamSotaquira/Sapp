<?php

namespace App\Models;

use App\Models\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory, BelongsToWorkspace;

    const STATUS_ACTIVE = 'active';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'code',
        'description',
        'company_id',
        'status',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['progress', 'service_requests_count'];

    // ==================== RELACIONES ====================

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Solicitudes de servicio vinculadas al proyecto.
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Tareas directas del proyecto (legacy).
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Requirements (legacy).
     */
    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS]);
    }

    // ==================== ACCESSORS ====================

    /**
     * Progreso basado en solicitudes vinculadas.
     */
    public function getProgressAttribute(): int
    {
        $total = $this->serviceRequests()->count();
        if ($total === 0) {
            return 0;
        }

        $closed = $this->serviceRequests()
            ->whereIn('status', ['RESUELTA', 'CERRADA'])
            ->count();

        return (int) round(($closed / $total) * 100);
    }

    public function getServiceRequestsCountAttribute(): int
    {
        return $this->serviceRequests()->count();
    }

    /**
     * Tiempo total invertido (horas) en todas las tareas de las solicitudes del proyecto.
     */
    public function getTotalHoursAttribute(): float
    {
        $taskIds = Task::whereIn('service_request_id', $this->serviceRequests()->pluck('id'))
            ->pluck('id');

        $totalMinutes = Task::whereIn('id', $taskIds)
            ->sum('actual_duration_minutes');

        return round($totalMinutes / 60, 1);
    }

    // ==================== HELPERS ====================

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Activo',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_ON_HOLD => 'En pausa',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'blue',
            self::STATUS_IN_PROGRESS => 'indigo',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_ON_HOLD => 'amber',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Genera un código único para el proyecto.
     */
    public static function generateCode(): string
    {
        $year = now()->format('Y');
        $sequence = static::where('code', 'like', "PRJ-{$year}-%")->count() + 1;

        return sprintf('PRJ-%s-%03d', $year, $sequence);
    }
}
