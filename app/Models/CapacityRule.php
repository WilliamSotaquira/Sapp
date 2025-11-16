<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapacityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'day_type',
        'max_impact_tasks_morning',
        'max_regular_tasks_afternoon',
        'impact_task_duration_minutes',
        'regular_task_duration_minutes',
        'buffer_between_tasks_minutes',
        'documentation_time_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_impact_tasks_morning' => 'integer',
        'max_regular_tasks_afternoon' => 'integer',
        'impact_task_duration_minutes' => 'integer',
        'regular_task_duration_minutes' => 'integer',
        'buffer_between_tasks_minutes' => 'integer',
        'documentation_time_minutes' => 'integer',
    ];

    // Relaciones
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('technician_id');
    }

    public function scopeForTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    // Método estático para obtener regla aplicable
    public static function getActiveRuleForTechnician($technicianId, $dayType = 'weekday')
    {
        // Primero buscar regla específica del técnico
        $rule = static::where('technician_id', $technicianId)
            ->where('day_type', $dayType)
            ->where('is_active', true)
            ->first();

        // Si no hay regla específica, usar regla global
        if (!$rule) {
            $rule = static::whereNull('technician_id')
                ->where('day_type', $dayType)
                ->where('is_active', true)
                ->first();
        }

        return $rule;
    }
}
