<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardTask extends Model
{
    protected $fillable = [
        'sub_service_id',
        'title',
        'description',
        'type',
        'priority',
        'estimated_hours',
        'technical_complexity',
        'technologies',
        'required_accesses',
        'environment',
        'technical_notes',
        'is_active',
        'order',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'technical_complexity' => 'integer',
        'order' => 'integer',
    ];

    // Relaciones
    public function subService()
    {
        return $this->belongsTo(SubService::class);
    }

    public function standardSubtasks()
    {
        return $this->hasMany(StandardSubtask::class)->orderBy('order');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSubService($query, $subServiceId)
    {
        return $query->where('sub_service_id', $subServiceId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('title');
    }
}

