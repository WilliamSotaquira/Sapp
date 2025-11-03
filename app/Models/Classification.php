<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classification extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'description', 'order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    public function getRequirementsCountAttribute()
    {
        return $this->requirements()->count();
    }
}
