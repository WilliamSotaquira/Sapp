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
        'is_control',
        'cost',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_control' => 'boolean',
        'cost' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Subservicios marcados como de control/verificación del líder.
     */
    public function scopeControl($query)
    {
        return $query->where('is_control', true);
    }

    /**
     * Resuelve el subservicio de CONTROL de un contrato dado.
     *
     * Sube por service -> family (service_families.contract_id) para encontrar
     * el subservicio marcado is_control dentro de ese contrato. Devuelve null si
     * el contrato aún no tiene uno configurado (en ese caso el líder debe elegirlo).
     */
    public static function controlForContract(?int $contractId): ?self
    {
        if (!$contractId) {
            return null;
        }

        return static::query()
            ->where('is_control', true)
            ->whereHas('service.family', function ($q) use ($contractId) {
                $q->where('contract_id', $contractId);
            })
            ->first();
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

    public function standardTasks()
    {
        return $this->hasMany(StandardTask::class)->active()->ordered();
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
