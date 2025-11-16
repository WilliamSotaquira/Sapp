<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'action',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
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
    public function scopeForTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
