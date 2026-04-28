<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Technician;
use App\Models\Traits\ServiceRequestConstants;
use App\Models\Traits\ServiceRequestScopes;
use App\Models\Traits\ServiceRequestWorkflow;
use App\Models\Traits\ServiceRequestAccessors;
use App\Models\Traits\ServiceRequestUtilities;
use Illuminate\Support\Facades\Schema;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;
    use ServiceRequestConstants, ServiceRequestScopes, ServiceRequestWorkflow, ServiceRequestAccessors, ServiceRequestUtilities;

    public const ENTRY_CHANNEL_CORPORATE_EMAIL = 'email_corporativo';
    public const ENTRY_CHANNEL_DIGITAL_EMAIL = 'email_digital';
    public const ENTRY_CHANNEL_WHATSAPP = 'whatsapp';
    public const ENTRY_CHANNEL_PHONE = 'telefono';
    public const ENTRY_CHANNEL_MEETING = 'reunion';

    protected $fillable = ['company_id', 'ticket_number', 'sla_id', 'sub_service_id', 'requested_by', 'entry_channel', 'is_reportable', 'assigned_to', 'title', 'description', 'web_routes', 'main_web_route', 'criticality_level', 'status', 'due_date', 'acceptance_deadline', 'response_deadline', 'resolution_deadline', 'accepted_at', 'responded_at', 'resolved_at', 'closed_at', 'resolution_notes', 'satisfaction_score', 'is_paused', 'pause_reason', 'paused_at', 'paused_by', 'resumed_at', 'total_paused_minutes', 'rejection_reason', 'rejected_at', 'rejected_by', 'requester_id', 'created_at'];

    protected $attributes = [
        'status' => 'PENDIENTE',
    ];

    protected $casts = [
        'acceptance_deadline' => 'datetime',
        'response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'due_date' => 'date',
        'accepted_at' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'is_paused' => 'boolean',
        'is_reportable' => 'boolean',
        'web_routes' => 'array',
        'status' => 'string',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['step_by_step_evidences', 'file_evidences', 'is_overdue', 'time_remaining', 'criticality_level_color', 'status_color'];

    public static function getEntryChannelOptions(): array
    {
        return [
            self::ENTRY_CHANNEL_CORPORATE_EMAIL => [
                'label' => 'Email corporativo',
                'emoji' => '🏢📧',
                'highlights' => [
                    'Solicitudes formales',
                    'Documentación oficial',
                    'Requerimientos de alta dirección',
                ],
            ],
            self::ENTRY_CHANNEL_DIGITAL_EMAIL => [
                'label' => 'Memorando',
                'emoji' => '📧',
                'highlights' => [
                    'Solicitudes automáticas',
                    'Portal web y formularios',
                    'Flujos digitales',
                ],
            ],
            self::ENTRY_CHANNEL_WHATSAPP => [
                'label' => 'WhatsApp',
                'emoji' => '📱',
                'highlights' => [
                    'Solicitudes rápidas',
                    'Coordinación inmediata',
                    'Consultas operativas',
                ],
            ],
            self::ENTRY_CHANNEL_PHONE => [
                'label' => 'Teléfono',
                'emoji' => '📞',
                'highlights' => [
                    'Urgencias',
                    'Consultas específicas',
                ],
            ],
            self::ENTRY_CHANNEL_MEETING => [
                'label' => 'Reunión',
                'emoji' => '👥',
                'highlights' => [
                    'Ordinarias (1 hora)',
                    'Seguimiento (periódicas)',
                    'Control (auditorías)',
                    'Coordinación (dependencias)',
                ],
            ],
        ];
    }

    public static function getEntryChannelValidationValues(): array
    {
        return array_keys(self::getEntryChannelOptions());
    }

    /**
     * Obtener opciones de estados disponibles
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_ACCEPTED => 'Aceptada',
            self::STATUS_IN_PROGRESS => 'En Progreso',
            self::STATUS_RESOLVED => 'Resuelta',
            self::STATUS_CLOSED => 'Cerrada',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_PAUSED => 'Pausada',
            self::STATUS_REOPENED => 'Reabierto',
        ];
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('workspace', function ($query) {
            $companyId = session('current_company_id');
            if ($companyId) {
                $query->where($query->getModel()->qualifyColumn('company_id'), $companyId);
            }
        });

        // Generar ticket_number automáticamente al crear
        static::creating(function ($model) {
            if (empty($model->ticket_number)) {
                try {
                    $model->ticket_number = static::generateProfessionalTicketNumber($model->sub_service_id, $model->criticality_level);
                } catch (\Exception $e) {
                    // Fallback si hay error
                    $model->ticket_number = 'SR-' . now()->format('Ymd-His') . '-' . strtoupper(substr(uniqid(), -4));
                    \Log::error('Error generando ticket profesional: ' . $e->getMessage());
                }
            }
        });

        static::saving(function ($model) {
            $model->validateWorkflowRules();
        });

        static::saved(function ($model) {
            if (!$model->wasChanged('assigned_to') || empty($model->assigned_to)) {
                return;
            }

            $technician = Technician::withTrashed()->where('user_id', $model->assigned_to)->first();
            if ($technician && method_exists($technician, 'trashed') && $technician->trashed()) {
                $technician->restore();
            }

            if (!$technician) {
                $technician = Technician::create([
                    'user_id' => (int) $model->assigned_to,
                    'status' => 'active',
                    'availability_status' => 'available',
                ]);
            }

            if (!$technician) {
                return;
            }

            // Solo asignar tareas aún sin técnico para no sobreescribir planificación existente.
            $model->tasks()
                ->whereNull('technician_id')
                ->update(['technician_id' => $technician->id]);
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
        return $this->belongsTo(Requester::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedTechnician()
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

    public function statusHistories()
    {
        return $this->hasMany(ServiceRequestStatusHistory::class)->orderBy('created_at', 'desc');
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
    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Esta relación se encuentra repetirda, eliminar una de las dos
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Relación con tareas del módulo de técnicos
     */
    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class);
    }

    public function cuts()
    {
        return $this->belongsToMany(Cut::class, 'cut_service_request')
            ->withTimestamps();
    }

    /**
     * Actualizar estado basado en tareas
     */
    public function updateStatusFromTasks()
    {
        if (in_array($this->status, ['RECHAZADA', self::STATUS_CANCELLED, self::STATUS_CLOSED], true)) {
            return;
        }

        $tasks = $this->tasks;

        if ($tasks->isEmpty()) {
            return;
        }

        $anyInProgress = $tasks->contains(fn($task) => $task->status === 'in_progress');
        $anyCompleted = $tasks->contains(fn($task) => $task->status === 'completed');

        // Completar tareas ya no resuelve ni cierra automáticamente la solicitud.
        // La transición a RESUELTA debe hacerse solo mediante el formulario de resolución.
        if (
            ($anyInProgress && $this->status !== self::STATUS_IN_PROGRESS)
            || ($anyCompleted && in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_REOPENED], true))
        ) {
            $this->ensureInProgressState($tasks);
        }
    }

    /**
     * Resolver solicitud desde lógica interna (sin formulario).
     */
    public function resolve(string $notes = 'Resolución completada', ?int $actualResolutionTime = null): bool
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolution_notes = $notes;
        if ($this->hasActualResolutionTimeColumn()) {
            $this->actual_resolution_time = $actualResolutionTime
                ?? $this->actual_resolution_time
                ?? 60;
        }
        $this->resolved_at = $this->resolved_at ?? now();

        try {
            return $this->save();
        } catch (\Throwable $e) {
            \Log::error('Error al resolver solicitud automáticamente: ' . $e->getMessage());
            return false;
        }
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

    protected function ensureInProgressState($tasks = null)
    {
        $tasks = $tasks ?: $this->tasks;
        $now = now();

        // Sincronizar assigned_to desde tareas cuando exista técnico en tareas
        // pero la solicitud aún no tenga usuario asignado.
        if (empty($this->assigned_to)) {
            $this->hydrateAssignedToFromTaskTechnician($tasks);
        }

        if ($this->status === self::STATUS_PENDING) {
            $assignedUserId = $this->assigned_to;
            if (!$assignedUserId) {
                $firstTaskTechnician = $tasks->first()?->technician;
                $assignedUserId = $firstTaskTechnician?->user_id ?? $firstTaskTechnician?->user?->id;
            }

            if ($assignedUserId) {
                $this->assigned_to = $assignedUserId;
            }

            $this->status = self::STATUS_ACCEPTED;
            $this->accepted_at = $this->accepted_at ?? $now;
            $this->save();
        }

        if ($this->status === self::STATUS_ACCEPTED || $this->status === self::STATUS_REOPENED) {
            $this->status = self::STATUS_IN_PROGRESS;
            // El reloj operativo inicia cuando hay ejecución real de tareas.
            $this->responded_at = $this->responded_at ?? $now;
            $this->save();
        } elseif ($this->status !== self::STATUS_IN_PROGRESS) {
            $this->update([
                'status' => self::STATUS_IN_PROGRESS,
                'responded_at' => $this->responded_at ?? $now,
            ]);
        } elseif (!$this->responded_at) {
            // Si ya está en proceso por migraciones/flujo anterior, inicializar marca operativa.
            $this->update(['responded_at' => $now]);
        }
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
            $this->hydrateAssignedToFromTaskTechnician();
            if (empty($this->assigned_to)) {
                throw new \Exception('No se puede establecer el estado EN PROCESO sin un técnico asignado.');
            }
        }

        $this->attributes['status'] = $value;
    }

    protected function hydrateAssignedToFromTaskTechnician($tasks = null): void
    {
        if (!empty($this->assigned_to)) {
            return;
        }

        $tasks = $tasks ?: (
            $this->relationLoaded('tasks')
                ? $this->tasks
                : $this->tasks()->select(['id', 'service_request_id', 'technician_id'])->get()
        );

        $technicianId = optional($tasks->first(fn($task) => !empty($task->technician_id)))->technician_id;
        if (empty($technicianId)) {
            return;
        }

        $userId = Technician::withTrashed()
            ->where('id', (int) $technicianId)
            ->value('user_id');

        if (!empty($userId)) {
            $this->assigned_to = (int) $userId;
        }
    }

    public function hasActualResolutionTimeColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn($this->getTable(), 'actual_resolution_time');
        }

        return (bool) $hasColumn;
    }

    // En app/Models/ServiceRequest.php
    public function hasWebRoutes(): bool
    {
        $webRoutes = $this->web_routes;

        // Si es null o vacío
        if (empty($webRoutes)) {
            return false;
        }

        // Si es array y tiene elementos
        if (is_array($webRoutes) && count($webRoutes) > 0) {
            return true;
        }

        // Si es string y no está vacío
        if (is_string($webRoutes) && !empty(trim($webRoutes))) {
            return true;
        }

        // Si es JSON string
        if (is_string($webRoutes)) {
            $decoded = json_decode($webRoutes, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope para solicitudes rechazadas
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'RECHAZADA');
    }

    /**
     * Verificar si la solicitud está rechazada
     */
    public function isRejected()
    {
        return $this->status === 'RECHAZADA';
    }
}
