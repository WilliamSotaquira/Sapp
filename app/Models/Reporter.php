<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reporter extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'department', 'phone', 'position', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    public function getActiveRequirementsCountAttribute()
    {
        return $this->requirements()->whereIn('status', ['pending', 'in_progress'])->count();
    }
}
