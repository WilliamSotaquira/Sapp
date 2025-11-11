<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\ServiceRequestConstants;
use App\Models\Traits\ServiceRequestScopes;
use App\Models\Traits\ServiceRequestWorkflow;
use App\Models\Traits\ServiceRequestAccessors;
use App\Models\Traits\ServiceRequestUtilities;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;
    use ServiceRequestConstants, ServiceRequestScopes, ServiceRequestWorkflow, ServiceRequestAccessors, ServiceRequestUtilities;

    protected $fillable = ['ticket_number', 'sla_id', 'sub_service_id', 'requested_by', 'assigned_to', 'title', 'description', 'web_routes', 'main_web_route', 'criticality_level', 'status', 'acceptance_deadline', 'response_deadline', 'resolution_deadline', 'accepted_at', 'responded_at', 'resolved_at', 'closed_at', 'resolution_notes', 'satisfaction_score', 'is_paused', 'pause_reason', 'paused_at', 'resumed_at', 'total_paused_minutes'];

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

    protected $appends = ['step_by_step_evidences', 'file_evidences', 'is_overdue', 'time_remaining', 'criticality_level_color', 'status_color'];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateWorkflowRules();
        });
    }

    // ==================== RELACIONES ====================
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

    public function childRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_request_id');
    }

    public function parentRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    // ==================== MÉTODOS ADICIONALES ====================

    /**
     * Corregir inconsistencias
     */
    public function fixInconsistency()
    {
        if ($this->status === self::STATUS_IN_PROGRESS && empty($this->assigned_to)) {
            \Log::warning("Corrigiendo inconsistencia en solicitud #{$this->ticket_number}");

            if ($this->accepted_at) {
                $this->status = self::STATUS_ACCEPTED;
            } else {
                $this->status = self::STATUS_PENDING;
            }

            return $this->save();
        }

        return false;
    }

    /**
     * Verificar consistencia
     */
    public function checkConsistency()
    {
        $issues = [];

        if ($this->status === self::STATUS_IN_PROGRESS && empty($this->assigned_to)) {
            $issues[] = 'Solicitud EN_PROCESO sin técnico asignado';
        }

        if ($this->resolved_at && !$this->accepted_at) {
            $issues[] = 'Tiene resolved_at pero no accepted_at';
        }

        if ($this->closed_at && !$this->resolved_at) {
            $issues[] = 'Tiene closed_at pero no resolved_at';
        }

        return [
            'is_consistent' => empty($issues),
            'issues' => $issues,
            'ticket_number' => $this->ticket_number,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
        ];
    }

    /**
     * Generar número de ticket profesional
     */
    public static function generateProfessionalTicketNumber($subServiceId, $criticalityLevel)
    {
        $subService = SubService::with(['service.family'])->find($subServiceId);

        if (!$subService) {
            throw new \Exception('Subservicio no encontrado');
        }

        $familyPrefix = self::generateFamilyCode($subService->service->family);
        $subServicePrefix = self::generateServiceCode($subService);
        $criticalityCode = self::getCriticalityCode($criticalityLevel);
        $datePart = date('ymd');

        $baseTicketNumber = "{$familyPrefix}-{$subServicePrefix}-{$criticalityCode}-{$datePart}-";

        $lastTicket = self::where('ticket_number', 'like', $baseTicketNumber . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastTicket ? ((int) substr($lastTicket->ticket_number, -3)) + 1 : 1;
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
            self::CRITICALITY_CRITICAL => 'C',
        ];

        return $criticalityCodes[$criticalityLevel] ?? 'U';
    }

    /**
     * Validar que no se pueda cambiar a EN_PROCESO sin técnico asignado
     */
    public function setStatusAttribute($value)
    {
        if ($value === 'EN_PROCESO' && empty($this->assigned_to)) {
            throw new \Exception('No se puede establecer el estado EN PROCESO sin un técnico asignado.');
        }

        $this->attributes['status'] = $value;
    }
}
