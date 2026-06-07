<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestType extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model and register event callbacks.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (RequestType $requestType) {
            if (
                $requestType->slug === 'general' &&
                $requestType->isDirty('is_active') &&
                $requestType->is_active === false
            ) {
                throw new \Exception("No se puede desactivar el tipo 'general'.");
            }
        });
    }

    /**
     * Scope: only active request types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by slug.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Relationship: service requests of this type.
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'request_type_id');
    }
}
