<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['service_id', 'name', 'code', 'description', 'is_active', 'cost', 'order'];

    public function service()
    {
        return $this->belongsTo(Service::class)->withDefault([
            'name' => 'Servicio no encontrado',
            'code' => 'N/A'
        ]);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function getApplicableSlasAttribute()
    {
        // Acceder a través de la relación con withDefault
        if ($this->service->family) {
            return $this->service->family->serviceLevelAgreements()->where('is_active', true)->get();
        }

        return collect();
    }
}
