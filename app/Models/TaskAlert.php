<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'alert_type',
        'message',
        'is_read',
        'is_dismissed',
        'alert_at',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'alert_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Tipos de alerta disponibles
     */
    public static $alertTypes = [
        'due_soon' => ['label' => 'Próximo a vencer', 'color' => 'yellow', 'icon' => 'fa-clock'],
        'overdue' => ['label' => 'Vencido', 'color' => 'red', 'icon' => 'fa-exclamation-triangle'],
        'blocked' => ['label' => 'Bloqueado', 'color' => 'orange', 'icon' => 'fa-ban'],
        'no_evidence' => ['label' => 'Sin evidencia', 'color' => 'purple', 'icon' => 'fa-file-alt'],
        'critical_pending' => ['label' => 'Crítico pendiente', 'color' => 'red', 'icon' => 'fa-fire'],
    ];

    // Relaciones
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false)->where('is_dismissed', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAdmin($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }

    // Métodos
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function dismiss()
    {
        $this->update(['is_dismissed' => true]);
    }

    // Accessors
    public function getAlertInfoAttribute()
    {
        return self::$alertTypes[$this->alert_type] ?? self::$alertTypes['critical_pending'];
    }

    public function getColorAttribute()
    {
        return $this->alert_info['color'];
    }

    public function getIconAttribute()
    {
        return $this->alert_info['icon'];
    }

    public function getLabelAttribute()
    {
        return $this->alert_info['label'];
    }

    /**
     * Crear alertas para tareas críticas próximas a vencer
     */
    public static function generateDueSoonAlerts()
    {
        $tomorrow = now()->addDay();
        
        $criticalTasks = Task::where('is_critical', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<=', $tomorrow)
            ->whereDate('due_date', '>=', now())
            ->get();

        foreach ($criticalTasks as $task) {
            // Verificar si ya existe una alerta activa
            $exists = self::where('task_id', $task->id)
                ->where('alert_type', 'due_soon')
                ->where('is_dismissed', false)
                ->exists();

            if (!$exists) {
                self::create([
                    'task_id' => $task->id,
                    'user_id' => null, // Para admin
                    'alert_type' => 'due_soon',
                    'message' => "La tarea crítica '{$task->title}' vence pronto",
                    'alert_at' => now(),
                ]);
            }
        }
    }

    /**
     * Crear alertas para tareas críticas vencidas
     */
    public static function generateOverdueAlerts()
    {
        $overdueTasks = Task::where('is_critical', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<', now())
            ->get();

        foreach ($overdueTasks as $task) {
            $exists = self::where('task_id', $task->id)
                ->where('alert_type', 'overdue')
                ->where('is_dismissed', false)
                ->exists();

            if (!$exists) {
                self::create([
                    'task_id' => $task->id,
                    'user_id' => null,
                    'alert_type' => 'overdue',
                    'message' => "¡URGENTE! La tarea crítica '{$task->title}' está vencida",
                    'alert_at' => now(),
                ]);
            }
        }
    }
}
