<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Unidad básica de tiempo en minutos (25 min)
     */
    const TIME_BLOCK_MINUTES = 25;

    protected $fillable = [
        'task_code',
        'type',
        'title',
        'description',
        'service_request_id',
        'technician_id',
        'project_id',
        'standard_task_id',
        'sla_id',
        'scheduled_date',
        'scheduled_time',
        'scheduled_start_time',
        'due_date',
        'due_time',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'estimated_hours',
        'actual_hours',
        'time_blocks',
        'priority',
        'is_critical',
        'status',
        'technical_complexity',
        'required_accesses',
        'dependencies',
        'technologies',
        'git_repository',
        'git_branch',
        'git_pr_url',
        'environment',
        'technical_notes',
        'requires_evidence',
        'evidence_completed',
        'research_time_minutes',
        'started_at',
        'blocked_at',
        'block_reason',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'due_date' => 'date',
        'estimated_duration_minutes' => 'integer',
        'actual_duration_minutes' => 'integer',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'time_blocks' => 'integer',
        'technical_complexity' => 'integer',
        'required_accesses' => 'array',
        'dependencies' => 'array',
        'technologies' => 'array',
        'research_time_minutes' => 'integer',
        'is_critical' => 'boolean',
        'requires_evidence' => 'boolean',
        'evidence_completed' => 'boolean',
        'started_at' => 'datetime',
        'blocked_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['is_overdue', 'time_spent', 'status_color', 'priority_color', 'calculated_duration', 'is_due_soon'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->task_code)) {
                $date = $model->scheduled_date ? Carbon::parse($model->scheduled_date) : now();
                $prefix = $model->is_critical ? 'CRI' : ($model->type === 'impact' ? 'IMP' : 'REG');
                $dateStr = $date->format('Ymd');

                $lastTask = static::where('task_code', 'like', "{$prefix}-{$dateStr}-%")
                    ->lockForUpdate()
                    ->orderBy('task_code', 'desc')
                    ->first();

                $sequence = 1;
                if ($lastTask) {
                    $parts = explode('-', $lastTask->task_code);
                    $sequence = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
                }

                $model->task_code = sprintf('%s-%s-%03d', $prefix, $dateStr, $sequence);
            }

            // Determinar si es crítica basado en prioridad
            if (in_array($model->priority, ['critical', 'high']) && $model->due_date) {
                $model->is_critical = true;
            }

            // Calcular bloques de tiempo
            $model->calculateTimeBlocks();
        });

        static::saving(function ($model) {
            // Recalcular bloques de tiempo cuando se guarda
            $model->calculateTimeBlocks();

            // Actualizar is_critical basado en prioridad y fecha vencimiento
            if (in_array($model->priority, ['critical', 'high']) && $model->due_date) {
                $model->is_critical = true;
            }
        });
    }

    /**
     * Calcula el número de bloques de 25 minutos necesarios
     */
    public function calculateTimeBlocks()
    {
        $totalMinutes = $this->getCalculatedDurationAttribute();
        $this->time_blocks = max(1, ceil($totalMinutes / self::TIME_BLOCK_MINUTES));
        return $this->time_blocks;
    }

    /**
     * Calcula la duración total basada en subtareas
     */
    public function getCalculatedDurationAttribute()
    {
        // Si tiene subtareas, usar la suma de sus tiempos
        if ($this->relationLoaded('subtasks') && $this->subtasks->count() > 0) {
            return $this->subtasks->sum('estimated_minutes');
        }

        // Cargar subtareas si no están cargadas
        $subtasksSum = $this->subtasks()->sum('estimated_minutes');
        if ($subtasksSum > 0) {
            return $subtasksSum;
        }

        // Fallback a estimated_hours o estimated_duration_minutes
        if ($this->estimated_hours) {
            return round($this->estimated_hours * 60);
        }

        return $this->estimated_duration_minutes ?? self::TIME_BLOCK_MINUTES;
    }

    /**
     * Determina la hora de programación según si es crítica o no
     * Críticas: Jornada de mañana (8:00 - 12:00)
     * No críticas: Jornada de tarde (13:00 - 17:00)
     */
    public function getSuggestedScheduleTime()
    {
        if ($this->is_critical) {
            return '08:00'; // Mañana para críticas
        }
        return '13:00'; // Tarde para no críticas
    }

    /**
     * Verifica si tiene toda la evidencia requerida
     */
    public function checkEvidenceComplete()
    {
        if (!$this->requires_evidence) {
            return true;
        }

        // Verificar que todas las subtareas tengan evidencia
        $subtasksWithoutEvidence = $this->subtasks()
            ->where('requires_evidence', true)
            ->where('evidence_completed', false)
            ->count();

        return $subtasksWithoutEvidence === 0;
    }

    /**
     * Divide la tarea en bloques de 25 minutos
     */
    public function getTimeBlocksArray()
    {
        $blocks = [];
        $totalMinutes = $this->calculated_duration;
        $blockNumber = 1;

        while ($totalMinutes > 0) {
            $blockMinutes = min(self::TIME_BLOCK_MINUTES, $totalMinutes);
            $blocks[] = [
                'number' => $blockNumber,
                'minutes' => $blockMinutes,
                'label' => "Bloque {$blockNumber} ({$blockMinutes} min)",
            ];
            $totalMinutes -= $blockMinutes;
            $blockNumber++;
        }

        return $blocks;
    }

    // Relaciones
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function standardTask()
    {
        return $this->belongsTo(StandardTask::class);
    }

    public function sla()
    {
        return $this->belongsTo(ServiceLevelAgreement::class);
    }

    public function history()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function slaCompliance()
    {
        return $this->hasOne(SlaCompliance::class);
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function dependents()
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    public function gitAssociations()
    {
        return $this->hasMany(TaskGitAssociation::class);
    }

    public function knowledgeBase()
    {
        return $this->hasMany(KnowledgeBaseLink::class);
    }

    public function subtasks()
    {
        return $this->hasMany(Subtask::class)->orderBy('order');
    }

    public function checklists()
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('order');
    }

    public function scheduleBlock()
    {
        return $this->hasOne(ScheduleBlock::class);
    }

    public function alerts()
    {
        return $this->hasMany(TaskAlert::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeImpact($query)
    {
        return $query->where('type', 'impact');
    }

    public function scopeRegular($query)
    {
        return $query->where('type', 'regular');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    public function scopeForTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['critical', 'high']);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeNonCritical($query)
    {
        return $query->where('is_critical', false);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueSoon($query, $hours = 24)
    {
        return $query->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addHours($hours)])
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeNeedsEvidence($query)
    {
        return $query->where('requires_evidence', true)
            ->where('evidence_completed', false)
            ->where('status', 'completed');
    }

    /**
     * Scope para tareas visibles según el usuario
     * Administrador: ve todas
     * Técnico: no ve las del administrador
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query; // Admin ve todo
        }

        // Técnico: excluir tareas de administradores
        return $query->whereHas('technician.user', function ($q) {
            $q->where('role', '!=', 'admin');
        });
    }

    // Métodos de utilidad
    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->addHistory('started', auth()->id());
    }

    public function complete($notes = null)
    {
        $duration = $this->started_at ? now()->diffInMinutes($this->started_at) : null;

        // Verificar evidencia antes de completar
        $this->evidence_completed = $this->checkEvidenceComplete();

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_duration_minutes' => $duration,
            'evidence_completed' => $this->evidence_completed,
        ]);

        if ($notes) {
            $this->update(['technical_notes' => $notes]);
        }

        $this->addHistory('completed', auth()->id(), $notes);

        if ($this->serviceRequest) {
            $this->serviceRequest->updateStatusFromTasks();
        }
    }

    public function block($reason)
    {
        $this->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'block_reason' => $reason,
        ]);

        $this->addHistory('blocked', auth()->id(), $reason);

        // Crear alerta de bloqueo
        TaskAlert::create([
            'task_id' => $this->id,
            'alert_type' => 'blocked',
            'message' => "Tarea bloqueada: {$reason}",
            'alert_at' => now(),
        ]);
    }

    public function unblock()
    {
        $this->update([
            'status' => 'pending',
            'blocked_at' => null,
            'block_reason' => null,
        ]);

        $this->addHistory('unblocked', auth()->id());
    }

    public function addHistory($action, $userId, $notes = null, $metadata = null)
    {
        return $this->history()->create([
            'action' => $action,
            'user_id' => $userId,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        if (!$this->due_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        $dueDateTime = Carbon::parse($this->due_date->format('Y-m-d') . ' ' . ($this->due_time ?? '23:59:59'));
        return now()->gt($dueDateTime);
    }

    public function getIsDueSoonAttribute()
    {
        if (!$this->due_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        $dueDateTime = Carbon::parse($this->due_date->format('Y-m-d') . ' ' . ($this->due_time ?? '23:59:59'));
        return now()->diffInHours($dueDateTime, false) <= 24 && now()->lt($dueDateTime);
    }

    public function getTimeSpentAttribute()
    {
        if ($this->started_at && !$this->completed_at) {
            return now()->diffInMinutes($this->started_at);
        }

        return $this->actual_duration_minutes ?? 0;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'blue',
            'blocked' => 'red',
            'in_review' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'gray',
            'rescheduled' => 'orange',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute()
    {
        if ($this->is_critical) {
            return '🔴';
        }
        return $this->type === 'impact' ? '🟠' : '🟡';
    }

    public function getScheduledTimeAttribute()
    {
        return $this->attributes['scheduled_time'] ?? $this->attributes['scheduled_start_time'] ?? null;
    }

    public function getFormattedDurationAttribute()
    {
        $totalMins = $this->calculated_duration;
        $hours = floor($totalMins / 60);
        $mins = $totalMins % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}min";
        }
    }

    public function getTimeBlocksLabelAttribute()
    {
        $blocks = $this->time_blocks ?? 1;
        return $blocks . ' ' . ($blocks === 1 ? 'bloque' : 'bloques') . ' (25 min c/u)';
    }

    public function getSubtasksProgressAttribute()
    {
        $total = $this->subtasks()->count();
        if ($total === 0) {
            return 100;
        }

        $completed = $this->subtasks()->where('status', 'completed')->count();
        return round(($completed / $total) * 100);
    }

    public function getEvidenceStatusAttribute()
    {
        if (!$this->requires_evidence) {
            return 'not_required';
        }

        return $this->evidence_completed ? 'complete' : 'pending';
    }
}
