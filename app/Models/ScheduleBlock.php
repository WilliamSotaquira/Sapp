<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'block_date',
        'block_type',
        'start_time',
        'end_time',
        'task_id',
        'status',
        'work_type',
        'block_reason',
    ];

    protected $casts = [
        'block_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
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

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    // Métodos
    public function getDurationMinutesAttribute()
    {
        return \Carbon\Carbon::parse($this->end_time)->diffInMinutes(\Carbon\Carbon::parse($this->start_time));
    }

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
}
