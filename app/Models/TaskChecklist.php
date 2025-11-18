<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'title',
        'is_completed',
        'order',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'order' => 'integer',
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

    public function toggle()
    {
        $this->update([
            'is_completed' => !$this->is_completed,
            'completed_at' => $this->is_completed ? null : now(),
        ]);
    }
}
