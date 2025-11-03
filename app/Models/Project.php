<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'start_date', 'end_date', 'budget', 'status', 'progress'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2'
    ];

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
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
