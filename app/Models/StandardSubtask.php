<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardSubtask extends Model
{
    protected $fillable = [
        'standard_task_id',
        'title',
        'description',
        'priority',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Relaciones
    public function standardTask()
    {
        return $this->belongsTo(StandardTask::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('title');
    }
}

