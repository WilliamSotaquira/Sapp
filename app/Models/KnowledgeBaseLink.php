<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'title',
        'url',
        'content',
        'type',
        'is_reusable',
    ];

    protected $casts = [
        'is_reusable' => 'boolean',
    ];

    // Relaciones
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Scopes
    public function scopeReusable($query)
    {
        return $query->where('is_reusable', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
