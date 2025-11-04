<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Managers\ServiceRequestStateManager;
use App\Models\Managers\ServiceRequestEvidenceManager;
use App\Models\Managers\ServiceRequestWebRoutesManager;
use App\Models\Managers\ServiceRequestTimelineManager;

class ServiceRequest extends Model
{

    const STATUS_PENDING = 'PENDIENTE';
    const STATUS_ACCEPTED = 'ACEPTADA';
    const STATUS_IN_PROGRESS = 'EN_PROCESO';
    const STATUS_RESOLVED = 'RESUELTA';
    const STATUS_CLOSED = 'CERRADA';
    const STATUS_CANCELLED = 'CANCELADA';

    use HasFactory, SoftDeletes;
    use ServiceRequestStateManager,
        ServiceRequestEvidenceManager,
        ServiceRequestWebRoutesManager,
        ServiceRequestTimelineManager;

    protected $fillable = [
        'ticket_number',
        'sla_id',
        'sub_service_id',
        'requested_by',
        'assigned_to',
        'title',
        'description',
        'web_routes',
        'main_web_route',
        'criticality_level',
        'status',
        'acceptance_deadline',
        'response_deadline',
        'resolution_deadline',
        'accepted_at',
        'responded_at',
        'resolved_at',
        'closed_at',
        'resolution_notes',
        'satisfaction_score',
        'is_paused',
        'pause_reason',
        'paused_at',
        'resumed_at',
        'total_paused_minutes'
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
        'web_routes' => 'array',
        'status' => 'string',
    ];

    // =============================================
    // RELACIONES BÁSICAS
    // =============================================

    public function subService()
    {
        return $this->belongsTo(SubService::class, 'sub_service_id');
    }

    public function sla()
    {
        return $this->belongsTo(ServiceLevelAgreement::class, 'sla_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function breachLogs()
    {
        return $this->hasMany(SlaBreachLog::class);
    }

    public function evidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id');
    }

    // En tu modelo ServiceRequest (o ServiceRequestEvidenceManager)
    public function hasAnyEvidenceForResolution()
    {
        return $this->evidences()
            ->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])
            ->count() > 0;
    }
    // Método para obtener evidencias paso a paso
    public function getStepByStepEvidencesAttribute()
    {
        return $this->evidences()->where('evidence_type', 'PASO_A_PASO')->get();
    }
    // Método para obtener archivos adjuntos
    public function getFileEvidencesAttribute()
    {
        return $this->evidences()->where('evidence_type', 'ARCHIVO')->get();
    }

    // =============================================
    // MÉTODOS BÁSICOS (NO DELEGABLES)
    // =============================================

    /**
     * Verificar si la solicitud está vencida
     */
    public function isOverdue()
    {
        if ($this->closed_at) {
            return false;
        }

        $deadline = $this->resolution_deadline;
        if (!$deadline) {
            return false;
        }

        return now()->greaterThan($deadline);
    }

    /**
     * Obtener tiempo restante para vencimiento
     */
    public function getTimeRemaining()
    {
        if ($this->closed_at || !$this->resolution_deadline) {
            return null;
        }

        $now = now();
        if ($now->greaterThan($this->resolution_deadline)) {
            return 'Vencido';
        }

        return $this->formatDuration($now->diff($this->resolution_deadline));
    }

    /**
     * Formatear duración para intervalos de fecha
     */
    public function formatDuration($duration)
    {
        if (!$duration) {
            return '0 minutos';
        }

        $parts = [];

        if ($duration->days > 0) {
            $parts[] = $duration->days . ' día' . ($duration->days > 1 ? 's' : '');
        }

        if ($duration->h > 0) {
            $parts[] = $duration->h . ' hora' . ($duration->h > 1 ? 's' : '');
        }

        if ($duration->i > 0) {
            $parts[] = $duration->i . ' minuto' . ($duration->i > 1 ? 's' : '');
        }

        return implode(', ', $parts) ?: '0 minutos';
    }

    public function isResolved()
    {
        return strtoupper($this->status) === 'RESUELTA';
    }
    public function canBeClosed()
    {
        $currentStatus = strtoupper(trim($this->status));
        return $currentStatus === 'RESUELTA' && $currentStatus !== 'CERRADA';
    }
}
