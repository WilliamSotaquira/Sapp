<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subtask extends Model
{
    use HasFactory;

    /**
     * Unidad básica de tiempo en minutos
     */
    const DEFAULT_TIME_MINUTES = 25;

    protected $fillable = [
        'task_id',
        'title',
        'notes',
        'status',
        'priority',
        'order',
        'estimated_minutes',
        'actual_minutes',
        'requires_evidence',
        'evidence_completed',
        'is_completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'estimated_minutes' => 'integer',
        'actual_minutes' => 'integer',
        'is_completed' => 'boolean',
        'requires_evidence' => 'boolean',
        'evidence_completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'estimated_minutes' => 25, // Por defecto 25 minutos
        'requires_evidence' => true,
        'evidence_completed' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        // Al crear o actualizar una subtarea, recalcular la tarea padre
        static::saved(function ($subtask) {
            if ($subtask->task) {
                $subtask->task->calculateTimeBlocks();
                $subtask->task->save();
            }
        });

        static::deleted(function ($subtask) {
            if ($subtask->task) {
                $subtask->task->calculateTimeBlocks();
                $subtask->task->save();
            }
        });
    }

    // Relaciones
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

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

    public function scopeNeedsEvidence($query)
    {
        return $query->where('requires_evidence', true)
            ->where('evidence_completed', false);
    }

    // Métodos
    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete()
    {
        $duration = $this->started_at ? now()->diffInMinutes($this->started_at) : null;

        $this->update([
            'status' => 'completed',
            'is_completed' => true,
            'completed_at' => now(),
            'actual_minutes' => $duration,
        ]);

        // Verificar si todas las subtareas de la tarea padre están completas
        $this->checkParentTaskCompletion();
    }

    public function markEvidenceComplete()
    {
        $this->update(['evidence_completed' => true]);

        // Verificar si la tarea padre tiene toda la evidencia
        if ($this->task) {
            $this->task->evidence_completed = $this->task->checkEvidenceComplete();
            $this->task->save();
        }
    }

    public function isCompleted()
    {
        return $this->status === 'completed' || $this->is_completed;
    }

    /**
     * Verifica si todas las subtareas están completas para actualizar la tarea padre
     */
    protected function checkParentTaskCompletion()
    {
        if (!$this->task) {
            return;
        }

        $pendingSubtasks = $this->task->subtasks()
            ->where('status', '!=', 'completed')
            ->count();

        // Si todas las subtareas están completas, sugerir completar la tarea
        if ($pendingSubtasks === 0) {
            // Opcionalmente auto-completar la tarea padre
            // $this->task->complete();
        }
    }

    // Accessors
    public function getFormattedDurationAttribute()
    {
        $minutes = $this->estimated_minutes ?? self::DEFAULT_TIME_MINUTES;
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
        }
        return "{$minutes}min";
    }

    public function getTimeBlocksAttribute()
    {
        $minutes = $this->estimated_minutes ?? self::DEFAULT_TIME_MINUTES;
        return ceil($minutes / self::DEFAULT_TIME_MINUTES);
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getEvidenceStatusAttribute()
    {
        if (!$this->requires_evidence) {
            return 'not_required';
        }
        return $this->evidence_completed ? 'complete' : 'pending';
    }

    public function getActualVsEstimatedAttribute()
    {
        if (!$this->actual_minutes) {
            return null;
        }

        $estimated = $this->estimated_minutes ?? self::DEFAULT_TIME_MINUTES;
        $diff = $this->actual_minutes - $estimated;

        if ($diff > 0) {
            return ['status' => 'over', 'diff' => $diff, 'label' => "+{$diff}min"];
        } elseif ($diff < 0) {
            return ['status' => 'under', 'diff' => abs($diff), 'label' => abs($diff) . "min antes"];
        }
        return ['status' => 'exact', 'diff' => 0, 'label' => "Exacto"];
    }
}
