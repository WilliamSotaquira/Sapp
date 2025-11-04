<?php
// app/Models/Service.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_family_id',
        'name',
        'code',
        'description',
        'is_active',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function family()
    {
        return $this->belongsTo(ServiceFamily::class, 'service_family_id');
    }

    public function subServices()
    {
        return $this->hasMany(SubService::class);
    }

    /**
     * Scope para servicios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para servicios ordenados
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
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
                    throw new \Exception('El código de servicio ya está en uso.');
                }
            }
        });
    }

    /**
     * Obtener sub-servicios activos (método de relación, no scope)
     */
    public function activeSubServices()
    {
        return $this->hasMany(SubService::class)->where('is_active', true);
    }
}
