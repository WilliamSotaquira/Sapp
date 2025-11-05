<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceLevelAgreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'service_subservice_id',
        'service_family_id',
        'service_id',
        'criticality_level',
        'response_time_hours',
        'resolution_time_hours',
        'acceptance_time_minutes',
        'response_time_minutes',
        'resolution_time_minutes',
        'availability_percentage',
        'conditions',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'availability_percentage' => 'decimal:2'
    ];

    // Relaciones
    public function serviceSubservice()
    {
        return $this->belongsTo(ServiceSubservice::class);
    }

    public function serviceFamily()
    {
        return $this->belongsTo(ServiceFamily::class);
    }

    /**
     * Relación CORREGIDA: service_requests tiene sla_id
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'sla_id');
    }
}
