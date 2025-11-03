<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'code', 'reporter_id', 'classification_id',
        'project_id', 'parent_id', 'priority', 'status'
    ];

    // Relaciones básicas
    public function reporter()
    {
        return $this->belongsTo(Reporter::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(Requirement::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Requirement::class, 'parent_id');
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class);
    }

    // Scopes básicos (sin due_date)
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    // Remover el scope overdue por ahora
    // public function scopeOverdue($query)
    // {
    //     return $query->where('due_date', '<', now())
    //                 ->whereIn('status', ['pending', 'in_progress']);
    // }

    // Métodos de ayuda
    public function getPriorityColor()
    {
        return match($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'secondary'
        };
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($requirement) {
            if (empty($requirement->code)) {
                $requirement->code = 'REQ-' . now()->format('Ymd-His') . '-' . rand(100, 999);
            }
        });
    }
}
