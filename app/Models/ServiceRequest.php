<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\Managers\ServiceRequestStateManager;
use App\Models\Managers\ServiceRequestEvidenceManager;
use App\Models\Managers\ServiceRequestWebRoutesManager;
use App\Models\Managers\ServiceRequestTimelineManager;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    const STATUS_PENDING = 'PENDIENTE';
    const STATUS_ACCEPTED = 'ACEPTADA';
    const STATUS_IN_PROGRESS = 'EN_PROCESO';
    const STATUS_RESOLVED = 'RESUELTA';
    const STATUS_CLOSED = 'CERRADA';
    const STATUS_CANCELLED = 'CANCELADA';

    const TYPE_SYSTEM = 'SISTEMA';
    const TYPE_STEP_BY_STEP = 'PASO_A_PASO';
    const TYPE_FILE = 'ARCHIVO';

    // Códigos de criticidad
    const CRITICALITY_LOW = 'BAJA';
    const CRITICALITY_MEDIUM = 'MEDIA';
    const CRITICALITY_HIGH = 'ALTA';
    const CRITICALITY_CRITICAL = 'CRITICA';

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = [
        'step_by_step_evidences',
        'file_evidences',
        'is_overdue',
        'time_remaining',
        'criticality_level_color',
        'status_color'
    ];

    // =============================================
    // RELACIONES
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación para solicitudes hijas (si existe jerarquía)
    public function childRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_request_id');
    }

    // Relación para solicitud padre (si existe jerarquía)
    public function parentRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getStepByStepEvidencesAttribute()
    {
        return $this->evidences()->where('evidence_type', self::TYPE_STEP_BY_STEP)->get();
    }

    public function getFileEvidencesAttribute()
    {
        return $this->evidences()->where('evidence_type', self::TYPE_FILE)->get();
    }

    public function getIsOverdueAttribute()
    {
        return $this->isOverdue();
    }

    public function getTimeRemainingAttribute()
    {
        return $this->getTimeRemaining();
    }

    public function getCriticalityLevelColorAttribute()
    {
        $colors = [
            self::CRITICALITY_LOW => 'success',
            self::CRITICALITY_MEDIUM => 'warning',
            self::CRITICALITY_HIGH => 'orange',
            self::CRITICALITY_CRITICAL => 'danger'
        ];

        return $colors[$this->criticality_level] ?? 'secondary';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'info',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'secondary',
            self::STATUS_CANCELLED => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // =============================================
    // MÉTODOS DE ESTADO
    // =============================================

    public function isOverdue()
    {
        if ($this->closed_at || $this->status === self::STATUS_CLOSED) {
            return false;
        }

        if (!$this->resolution_deadline) {
            return false;
        }

        return now()->greaterThan($this->resolution_deadline);
    }

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

    public function isResolved()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function canBeClosed()
    {
        return $this->status === self::STATUS_RESOLVED &&
               $this->status !== self::STATUS_CLOSED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isAccepted()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaused()
    {
        return $this->is_paused === true;
    }

    public function hasAnyEvidenceForResolution()
    {
        return $this->evidences()
            ->whereIn('evidence_type', [self::TYPE_STEP_BY_STEP, self::TYPE_FILE])
            ->exists();
    }

    /**
     * Verificar si tiene evidencias de paso a paso (para resolución)
     */
    public function hasStepByStepEvidences()
    {
        return $this->evidences()
            ->where('evidence_type', self::TYPE_STEP_BY_STEP)
            ->exists();
    }

    /**
     * Verificar si tiene evidencias de archivo
     */
    public function hasFileEvidences()
    {
        return $this->evidences()
            ->where('evidence_type', self::TYPE_FILE)
            ->exists();
    }

    // =============================================
    // MÉTODOS DE UTILIDAD
    // =============================================

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

    /**
     * Obtener tiempo transcurrido desde la creación
     */
    public function getElapsedTime()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Obtener tiempo hasta la resolución
     */
    public function getResolutionTime()
    {
        if (!$this->resolved_at) {
            return null;
        }

        return $this->accepted_at ? $this->accepted_at->diffForHumans($this->resolved_at, true) : null;
    }

    public static function generateProfessionalTicketNumber($subServiceId, $criticalityLevel)
    {
        $subService = SubService::with(['service.family'])->find($subServiceId);

        if (!$subService) {
            throw new \Exception('Subservicio no encontrado');
        }

        // Generar prefijos
        $familyPrefix = self::generateFamilyCode($subService->service->family);
        $subServicePrefix = self::generateServiceCode($subService);
        $criticalityCode = self::getCriticalityCode($criticalityLevel);

        $datePart = date('ymd');
        $baseTicketNumber = "{$familyPrefix}-{$subServicePrefix}-{$criticalityCode}-{$datePart}-";

        // Buscar el último ticket del día
        $lastTicket = self::where('ticket_number', 'like', $baseTicketNumber . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastTicket ?
            ((int) substr($lastTicket->ticket_number, -3)) + 1 : 1;

        $sequentialNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return $baseTicketNumber . $sequentialNumber;
    }

    private static function generateFamilyCode($family)
    {
        if (!empty($family->code)) {
            return strtoupper(substr($family->code, 0, 3));
        }

        $name = preg_replace('/[^a-zA-Z0-9]/', '', $family->name);
        return strtoupper(substr($name, 0, 3));
    }

    private static function generateServiceCode($service)
    {
        if (!empty($service->code)) {
            return strtoupper(substr($service->code, 0, 2));
        }

        $name = preg_replace('/[^a-zA-Z0-9]/', '', $service->name);
        return strtoupper(substr($name, 0, 2));
    }

    private static function getCriticalityCode($criticalityLevel)
    {
        $criticalityCodes = [
            self::CRITICALITY_LOW => 'L',
            self::CRITICALITY_MEDIUM => 'M',
            self::CRITICALITY_HIGH => 'H',
            self::CRITICALITY_CRITICAL => 'C'
        ];

        return $criticalityCodes[$criticalityLevel] ?? 'U';
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeOverdue($query)
    {
        return $query->where(function($q) {
            $q->whereNull('closed_at')
              ->where('resolution_deadline', '<', now())
              ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
        });
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopeOfCriticality($query, $criticalityLevel)
    {
        return $query->where('criticality_level', $criticalityLevel);
    }

    public function scopeWithEvidences($query)
    {
        return $query->whereHas('evidences');
    }

    public function scopeWithoutEvidences($query)
    {
        return $query->whereDoesntHave('evidences');
    }

    // =============================================
    // MÉTODOS DE TIEMPO
    // =============================================

    public function getTotalPausedTimeInMinutes()
    {
        return $this->total_paused_minutes ?? 0;
    }

    public function calculateEffectiveResolutionTime()
    {
        if (!$this->accepted_at || !$this->resolved_at) {
            return null;
        }

        $totalMinutes = $this->accepted_at->diffInMinutes($this->resolved_at);
        return max(0, $totalMinutes - $this->getTotalPausedTimeInMinutes());
    }

    /**
     * Obtener el tiempo total de pausa formateado
     */
    public function getFormattedPausedTime()
    {
        $minutes = $this->getTotalPausedTimeInMinutes();

        if ($minutes === 0) {
            return '0 minutos';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' hora' . ($hours > 1 ? 's' : '');
        }
        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes . ' minuto' . ($remainingMinutes > 1 ? 's' : '');
        }

        return implode(', ', $parts);
    }
}
