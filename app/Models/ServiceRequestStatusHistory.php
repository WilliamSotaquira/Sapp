<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'status',
        'previous_status',
        'comments',
        'changed_by',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'status_color', 'status_icon'];

    /**
     * Relación con la solicitud de servicio
     */
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Relación con el usuario que realizó el cambio
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope para obtener historial ordenado
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope para filtrar por solicitud
     */
    public function scopeForRequest($query, $serviceRequestId)
    {
        return $query->where('service_request_id', $serviceRequestId);
    }

    /**
     * Obtener el label amigable del estado
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'NUEVA' => 'Nueva',
            'EN_REVISION' => 'En Revisión',
            'ACEPTADA' => 'Aceptada',
            'EN_PROGRESO' => 'En Progreso',
            'PAUSADA' => 'Pausada',
            'RESUELTA' => 'Resuelta',
            'CERRADA' => 'Cerrada',
            'RECHAZADA' => 'Rechazada',
            'CANCELADA' => 'Cancelada',
        ];

        return $labels[$this->status] ?? str_replace('_', ' ', $this->status);
    }

    /**
     * Obtener color del estado
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'NUEVA' => 'blue',
            'EN_REVISION' => 'yellow',
            'ACEPTADA' => 'green',
            'EN_PROGRESO' => 'purple',
            'PAUSADA' => 'orange',
            'RESUELTA' => 'teal',
            'CERRADA' => 'gray',
            'RECHAZADA' => 'red',
            'CANCELADA' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Obtener icono del estado
     */
    public function getStatusIconAttribute()
    {
        $icons = [
            'NUEVA' => 'fa-star',
            'EN_REVISION' => 'fa-search',
            'ACEPTADA' => 'fa-check',
            'EN_PROGRESO' => 'fa-cog',
            'PAUSADA' => 'fa-pause-circle',
            'RESUELTA' => 'fa-check-circle',
            'CERRADA' => 'fa-lock',
            'RECHAZADA' => 'fa-times-circle',
            'CANCELADA' => 'fa-ban',
        ];

        return $icons[$this->status] ?? 'fa-circle';
    }
}
