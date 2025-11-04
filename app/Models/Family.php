<?php
// app/Models/Family.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relación con servicios
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Scope para familias activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

                // Verificar unicidad (excluyendo el propio registro)
                $exists = static::where('code', $model->code)
                    ->where('id', '!=', $model->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception('El código de familia ya está en uso.');
                }
            }
        });
    }
}
