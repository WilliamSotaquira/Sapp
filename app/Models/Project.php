<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'status'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    /**
     * Relación con tareas del módulo de técnicos
     */
    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class);
    }

    /**
     * Scope para proyectos activos
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'in_progress']);
    }

    public function getActiveRequirementsCountAttribute()
    {
        return $this->requirements()->whereIn('status', ['pending', 'in_progress'])->count();
    }

    public function calculateProgress()
    {
        $total = $this->requirements()->count();
        if ($total === 0) return 0;

        $completed = $this->requirements()->where('status', 'completed')->count();
        return round(($completed / $total) * 100);
    }
}
