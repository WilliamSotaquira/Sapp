<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceLevelAgreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_family_id', 'name', 'criticality_level',
        'acceptance_time_minutes', 'response_time_minutes',
        'resolution_time_minutes', 'conditions', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function serviceFamily()
    {
        return $this->belongsTo(ServiceFamily::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'sla_id');
    }
}
