<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number', 'sla_id', 'sub_service_id', 'requested_by', 'assigned_to',
        'title', 'description', 'criticality_level', 'status',
        'acceptance_deadline', 'response_deadline', 'resolution_deadline',
        'accepted_at', 'responded_at', 'resolved_at', 'closed_at',
        'resolution_notes', 'satisfaction_score',
        'is_paused', 'pause_reason', 'paused_at', 'resumed_at', 'total_paused_minutes'
    ];

    protected $casts = [
        'acceptance_deadline' => 'datetime',
        'response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'is_paused' => 'boolean',
    ];

    // RELACIÓN CON SUB-SERVICIO
    public function subService()
    {
        return $this->belongsTo(SubService::class, 'sub_service_id');
    }

    // RELACIÓN CON SLA
    public function sla()
    {
        return $this->belongsTo(ServiceLevelAgreement::class, 'sla_id');
    }

    // RELACIÓN CON USUARIO SOLICITANTE
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // RELACIÓN CON USUARIO ASIGNADO
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // RELACIÓN CON LOGS DE INCUMPLIMIENTO SLA
    public function breachLogs()
    {
        return $this->hasMany(SlaBreachLog::class);
    }

    // =============================================
    // NUEVAS RELACIONES PARA EVIDENCIAS
    // =============================================

    /**
     * Relación con todas las evidencias
     */
    public function evidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id');
    }

    /**
     * Relación con evidencias paso a paso
     */
    public function stepByStepEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
                    ->where('evidence_type', 'PASO_A_PASO')
                    ->orderBy('step_number');
    }

    /**
     * Relación con evidencias de archivo
     */
    public function fileEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
                    ->where('evidence_type', 'ARCHIVO');
    }

    /**
     * Relación con evidencias de comentario
     */
    public function commentEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
                    ->where('evidence_type', 'COMENTARIO');
    }

    /**
     * Relación con evidencias del sistema
     */
    public function systemEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
                    ->where('evidence_type', 'SISTEMA');
    }

    // =============================================
    // ACCESSORS PARA CONTAR EVIDENCIAS
    // =============================================

    /**
     * Contar todas las evidencias
     */
    public function getEvidencesCountAttribute()
    {
        return $this->evidences()->count();
    }

    /**
     * Contar evidencias paso a paso
     */
    public function getStepByStepEvidencesCountAttribute()
    {
        return $this->stepByStepEvidences()->count();
    }

    /**
     * Contar evidencias de archivo
     */
    public function getFileEvidencesCountAttribute()
    {
        return $this->fileEvidences()->count();
    }

    /**
     * Contar evidencias de comentario
     */
    public function getCommentEvidencesCountAttribute()
    {
        return $this->commentEvidences()->count();
    }

    // =============================================
    // MÉTODOS PARA VALIDACIÓN DE EVIDENCIAS
    // =============================================

    /**
     * Verificar si puede ser resuelta (tiene evidencias paso a paso)
     */
    public function canBeResolved()
    {
        return $this->status === 'EN_PROCESO' && $this->stepByStepEvidences()->exists();
    }

    /**
     * Verificar si tiene evidencias suficientes para resolver
     */
    public function hasRequiredEvidences()
    {
        return $this->stepByStepEvidences()->count() > 0;
    }

    /**
     * Obtener el siguiente número de paso disponible
     */
    public function getNextStepNumber()
    {
        $lastStep = $this->stepByStepEvidences()->max('step_number');
        return $lastStep ? $lastStep + 1 : 1;
    }

    // =============================================
    // MÉTODOS EXISTENTES (se mantienen igual)
    // =============================================

    /**
     * Pausar la solicitud
     */
    public function pause($reason = null)
    {
        $this->update([
            'is_paused' => true,
            'status' => 'PAUSADA',
            'pause_reason' => $reason,
            'paused_at' => now(),
        ]);
    }

    /**
     * Reanudar la solicitud
     */
    public function resume()
    {
        $pausedMinutes = 0;
        if ($this->paused_at) {
            $pausedMinutes = now()->diffInMinutes($this->paused_at);
        }

        $this->update([
            'is_paused' => false,
            'status' => 'EN_PROCESO',
            'resumed_at' => now(),
            'total_paused_minutes' => $this->total_paused_minutes + $pausedMinutes,
        ]);
    }

    /**
     * Verificar si está pausada
     */
    public function isPaused()
    {
        return $this->is_paused && $this->status === 'PAUSADA';
    }

    /**
     * Obtener el tiempo total pausado formateado
     */
    public function getTotalPausedTimeFormatted()
    {
        $minutes = $this->total_paused_minutes;

        if ($this->isPaused() && $this->paused_at) {
            $minutes += now()->diffInMinutes($this->paused_at);
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        } elseif ($minutes < 1440) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours} horas";
        } else {
            $days = floor($minutes / 1440);
            $hours = floor(($minutes % 1440) / 60);
            return $hours > 0 ? "{$days}d {$hours}h" : "{$days} días";
        }
    }

    /**
     * Calcular fechas límite considerando pausas
     */
    public function getAdjustedDeadlines()
    {
        $pausedMinutes = $this->total_paused_minutes;

        if ($this->isPaused() && $this->paused_at) {
            $pausedMinutes += now()->diffInMinutes($this->paused_at);
        }

        return [
            'acceptance_deadline' => $this->acceptance_deadline?->addMinutes($pausedMinutes),
            'response_deadline' => $this->response_deadline?->addMinutes($pausedMinutes),
            'resolution_deadline' => $this->resolution_deadline?->addMinutes($pausedMinutes),
        ];
    }
}
