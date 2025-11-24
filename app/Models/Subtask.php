<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'title',
        'notes',
        'status',
        'priority',
        'order',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
