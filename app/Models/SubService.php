<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'code',
        'description',
        'is_active',
        'cost',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // Relaciones
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function serviceSubservices()
    {
        return $this->hasMany(ServiceSubservice::class);
    }

    public function slas()
    {
        return $this->hasManyThrough(
            ServiceLevelAgreement::class,
            ServiceSubservice::class,
            'sub_service_id', // Foreign key on ServiceSubservice table
            'service_subservice_id', // Foreign key on SLA table
            'id', // Local key on SubService table
            'id' // Local key on ServiceSubservice table
        )->where('service_level_agreements.is_active', true);
    }

    /**
     * Validar que el código sea único
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->code) {
                $model->code = strtoupper(trim($model->code));

                $exists = static::where('code', $model->code)
                    ->where('id', '!=', $model->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception('El código de sub-servicio ya está en uso.');
                }
            }
        });
    }
}
