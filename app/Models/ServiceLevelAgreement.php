<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

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

    public function scopeForSubService(Builder $query, $subServiceId): Builder
    {
        if (empty($subServiceId)) {
            return $query->whereRaw('1 = 0');
        }

        if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
            return $query->where('sub_service_id', $subServiceId);
        }

        return $query->whereHas('serviceSubservice', function (Builder $subQuery) use ($subServiceId) {
            $subQuery->where('sub_service_id', $subServiceId);
        });
    }

    /**
     * Relación CORREGIDA: service_requests tiene sla_id
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'sla_id');
    }
}
