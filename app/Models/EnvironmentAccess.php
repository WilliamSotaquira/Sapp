<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvironmentAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'environment_name',
        'environment_type',
        'access_level',
        'has_access',
        'access_granted_at',
        'access_expires_at',
    ];

    protected $casts = [
        'has_access' => 'boolean',
        'access_granted_at' => 'date',
        'access_expires_at' => 'date',
    ];

    // Relaciones
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('has_access', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            });
    }

    public function scopeProduction($query)
    {
        return $query->where('environment_type', 'production');
    }

    // Métodos
    public function isExpired()
    {
        return $this->access_expires_at && now()->gt($this->access_expires_at);
    }
}
