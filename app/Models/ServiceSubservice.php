<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSubservice extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_family_id',
        'service_id',
        'sub_service_id',
        'name',
        'description',
        'is_active'
    ];

    // Relaciones
    public function serviceFamily()
    {
        return $this->belongsTo(ServiceFamily::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function subService()
    {
        return $this->belongsTo(SubService::class);
    }

    public function serviceLevelAgreements()
    {
        return $this->hasMany(ServiceLevelAgreement::class);
    }
}
