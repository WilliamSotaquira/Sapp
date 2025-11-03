<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'message', 'type', 'alert_date', 'expiration_date',
        'is_active', 'target_audience', 'metadata'
    ];

    protected $casts = [
        'alert_date' => 'datetime',
        'expiration_date' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('alert_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('expiration_date')
                          ->orWhere('expiration_date', '>', now());
                    });
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('alert_date', '>=', now()->subDays($days));
    }

    public function getAlertTypeClass()
    {
        return match($this->type) {
            'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            'success' => 'alert-success',
            default => 'alert-info'
        };
    }

    public function isExpired()
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }
}
