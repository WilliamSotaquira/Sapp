<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_code',
        'type',
        'title',
        'description',
        'service_request_id',
        'technician_id',
        'project_id',
        'sla_id',
        'scheduled_date',
        'scheduled_time',
        'scheduled_start_time',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'estimated_hours',
        'actual_hours',
        'priority',
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
        'research_time_minutes',
        'started_at',
        'blocked_at',
        'block_reason',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'estimated_duration_minutes' => 'integer',
        'actual_duration_minutes' => 'integer',
        'estimated_hours' => 'decimal:1',
        'actual_hours' => 'decimal:1',
        'technical_complexity' => 'integer',
        'required_accesses' => 'array',
        'dependencies' => 'array',
        'technologies' => 'array',
        'research_time_minutes' => 'integer',
        'started_at' => 'datetime',
        'blocked_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['is_overdue', 'time_spent', 'status_color', 'priority_color'];

    // Boot del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->task_code)) {
                $model->task_code = static::generateTaskCode($model->type, $model->scheduled_date);
            }
        });
    }

    // Generación automática de código de tarea
    public static function generateTaskCode($type, $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        $prefix = $type === 'impact' ? 'IMP' : 'REG';
        $dateStr = $date->format('Ymd');

        $lastTask = static::where('task_code', 'like', "{$prefix}-{$dateStr}-%")
            ->orderBy('task_code', 'desc')
            ->first();

        $sequence = 1;
        if ($lastTask) {
            $parts = explode('-', $lastTask->task_code);
            $sequence = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $dateStr, $sequence);
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

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_duration_minutes' => $duration,
        ]);

        if ($notes) {
            $this->update(['technical_notes' => $notes]);
        }

        $this->addHistory('completed', auth()->id(), $notes);

        // Actualizar service request relacionado
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
        if (!$this->scheduled_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        // Usar scheduled_start_time si existe, sino usar inicio del día
        $time = $this->scheduled_start_time ?? '00:00:00';
        $scheduledDateTime = Carbon::parse($this->scheduled_date->format('Y-m-d') . ' ' . $time);
        return now()->gt($scheduledDateTime);
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
        return $this->type === 'impact' ? '🔴' : '🟡';
    }

    public function getScheduledTimeAttribute()
    {
        // Accessor para backward compatibility: scheduled_time retorna scheduled_start_time
        return $this->attributes['scheduled_time'] ?? $this->attributes['scheduled_start_time'] ?? null;
    }

    public function getFormattedDurationAttribute()
    {
        // Priorizar estimated_hours si existe
        if ($this->estimated_hours) {
            // Usar minutos exactos sin redondear a múltiplos de 5
            $totalMins = round($this->estimated_hours * 60);
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

        // Fallback a estimated_duration_minutes
        $minutes = $this->estimated_duration_minutes ?? 0;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}min";
        }
        return "{$mins}min";
    }
}
