<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ScheduleBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'block_date',
        'block_type',
        'title',
        'description',
        'start_time',
        'end_time',
        'task_id',
        'status',
        'work_type',
        'block_reason',
        'color',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected $casts = [
        'block_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_recurring' => 'boolean',
        'recurrence_pattern' => 'array',
    ];

    /**
     * Tipos de bloqueo disponibles con sus colores e íconos
     */
    public static $blockTypes = [
        'meeting' => ['label' => 'Reunión', 'color' => '#3B82F6', 'icon' => 'fa-users'],
        'lunch' => ['label' => 'Almuerzo', 'color' => '#F59E0B', 'icon' => 'fa-utensils'],
        'break' => ['label' => 'Descanso', 'color' => '#10B981', 'icon' => 'fa-coffee'],
        'unavailable' => ['label' => 'No Disponible', 'color' => '#EF4444', 'icon' => 'fa-ban'],
        'vacation' => ['label' => 'Vacaciones', 'color' => '#8B5CF6', 'icon' => 'fa-umbrella-beach'],
        'training' => ['label' => 'Capacitación', 'color' => '#06B6D4', 'icon' => 'fa-graduation-cap'],
        'other' => ['label' => 'Otro', 'color' => '#6B7280', 'icon' => 'fa-calendar-times'],
    ];

    // Relaciones
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('block_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('block_date', [$startDate, $endDate]);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeForTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeBlocksOnly($query)
    {
        return $query->whereNotNull('block_type')
                     ->whereIn('block_type', array_keys(self::$blockTypes));
    }

    // Accessors
    public function getDurationMinutesAttribute()
    {
        return Carbon::parse($this->end_time)->diffInMinutes(Carbon::parse($this->start_time));
    }

    public function getDurationHoursAttribute()
    {
        return round($this->duration_minutes / 60, 2);
    }

    public function getBlockInfoAttribute()
    {
        $type = $this->block_type ?? 'other';
        return self::$blockTypes[$type] ?? self::$blockTypes['other'];
    }

    public function getBlockColorAttribute()
    {
        if ($this->color) {
            return $this->color;
        }
        return $this->block_info['color'];
    }

    public function getBlockIconAttribute()
    {
        return $this->block_info['icon'];
    }

    public function getBlockLabelAttribute()
    {
        return $this->block_info['label'];
    }

    public function getFormattedTimeRangeAttribute()
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');
        return "{$start} - {$end}";
    }

    // Métodos
    public function assignTask(Task $task)
    {
        $this->update([
            'task_id' => $task->id,
            'status' => 'occupied',
        ]);
    }

    public function release()
    {
        $this->update([
            'task_id' => null,
            'status' => 'available',
        ]);
    }

    /**
     * Verifica si el bloqueo se superpone con otro rango de tiempo
     */
    public function overlapsWithTimeRange($startTime, $endTime)
    {
        $blockStart = Carbon::parse($this->start_time);
        $blockEnd = Carbon::parse($this->end_time);
        $checkStart = Carbon::parse($startTime);
        $checkEnd = Carbon::parse($endTime);

        return $blockStart < $checkEnd && $blockEnd > $checkStart;
    }

    /**
     * Crea bloqueos recurrentes basados en el patrón
     */
    public static function createRecurring($data, $untilDate)
    {
        $blocks = [];
        $currentDate = Carbon::parse($data['block_date']);
        $endDate = Carbon::parse($untilDate);
        $pattern = $data['recurrence_pattern'] ?? ['type' => 'daily'];

        while ($currentDate <= $endDate) {
            $blockData = array_merge($data, ['block_date' => $currentDate->toDateString()]);
            $blocks[] = self::create($blockData);

            switch ($pattern['type']) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
                default:
                    $currentDate->addDay();
            }
        }

        return $blocks;
    }
}
