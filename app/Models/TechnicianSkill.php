<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'skill_name',
        'proficiency_level',
        'years_experience',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'years_experience' => 'integer',
    ];

    // Relaciones
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeExpert($query)
    {
        return $query->where('proficiency_level', 'expert');
    }

    public function scopeAdvanced($query)
    {
        return $query->whereIn('proficiency_level', ['advanced', 'expert']);
    }
}
