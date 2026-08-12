<?php

namespace App\Services;

use App\Models\OperationalAlert;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Models\SlaBreachLog;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor de evaluación de alertas operativas.
 *
 * Evalúa todas las solicitudes y tareas activas contra los umbrales configurados
 * y genera alertas cuando se detectan condiciones que requieren atención.
 *
 * El motor es idempotente: no genera alertas duplicadas para la misma condición.
 * Si la condición desaparece y auto_resolve está habilitado, resuelve la alerta automáticamente.
 */
class OperationalAlertService
{
    private AlertConfigService $config;
    private int $alertsGenerated = 0;
    private int $alertsResolved = 0;
    private array $summary = [];

    public function __construct(AlertConfigService $config)
    {
        $this->config = $config;
    }

    /**
     * Ejecutar evaluación completa de todas las solicitudes y tareas activas.
     *
     * @param int|null $companyId Filtrar por company (null = todas)
     * @return array Resumen de la ejecución
     */
    public function evaluate(?int $companyId = null): array
    {
        if (!$this->config->systemEnabled()) {
            return [
                'status' => 'disabled',
                'message' => 'El sistema de alertas está desactivado.',
                'alerts_generated' => 0,
                'alerts_resolved' => 0,
            ];
        }

        $this->alertsGenerated = 0;
        $this->alertsResolved = 0;
        $this->summary = [];

        $startTime = microtime(true);

        try {
            // Evaluar solicitudes de servicio
            $this->evaluateServiceRequests($companyId);

            // Evaluar tareas bloqueadas
            $this->evaluateBlockedTasks($companyId);

            // Resolver alertas que ya no aplican
            if ($this->config->autoResolveEnabled()) {
                $this->autoResolveStaleAlerts();
            }
        } catch (\Exception $e) {
            Log::error('Error en motor de alertas operativas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'alerts_generated' => $this->alertsGenerated,
                'alerts_resolved' => $this->alertsResolved,
            ];
        }

        $duration = round(microtime(true) - $startTime, 2);

        return [
            'status' => 'success',
            'duration_seconds' => $duration,
            'alerts_generated' => $this->alertsGenerated,
            'alerts_resolved' => $this->alertsResolved,
            'summary' => $this->summary,
        ];
    }

    // ==================== EVALUADORES DE SOLICITUDES ====================

    /**
     * Evaluar todas las solicitudes activas.
     */
    private function evaluateServiceRequests(?int $companyId): void
    {
        $activeStatuses = [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_PAUSED,
            ServiceRequest::STATUS_REOPENED,
        ];

        $query = ServiceRequest::withoutGlobalScopes()
            ->whereIn('status', $activeStatuses)
            ->with(['sla', 'evidences']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->chunkById(100, function ($requests) {
            foreach ($requests as $request) {
                $this->evaluateSingleRequest($request);
            }
        });
    }

    /**
     * Evaluar una solicitud individual contra todas las reglas.
     */
    private function evaluateSingleRequest(ServiceRequest $request): void
    {
        $this->checkPendingAcceptance($request);
        $this->checkHighPriorityIdle($request);
        $this->checkSlaAtRisk($request);
        $this->checkSlaBreached($request);
        $this->checkStaleRequest($request);
        $this->checkPausedTooLong($request);
        $this->checkOverdueResolution($request);
    }

    /**
     * Regla: Solicitud pendiente de aceptación por demasiado tiempo.
     */
    private function checkPendingAcceptance(ServiceRequest $request): void
    {
        if ($request->status !== ServiceRequest::STATUS_PENDING) {
            return;
        }

        $hoursThreshold = $this->config->pendingAcceptanceHours();
        $hoursSinceCreation = $request->created_at->diffInHours(now());

        if ($hoursSinceCreation >= $hoursThreshold) {
            $this->createAlert(
                $request,
                OperationalAlert::TYPE_PENDING_ACCEPTANCE,
                $this->deriveSeverityFromPriority($request->priority_level),
                "Solicitud {$request->ticket_number} sin aceptar ({$hoursSinceCreation}h)",
                [
                    'hours_waiting' => $hoursSinceCreation,
                    'threshold_hours' => $hoursThreshold,
                    'priority_level' => $request->priority_level,
                ]
            );
        }
    }

    /**
     * Regla: Solicitud de alta prioridad (P0/P1) sin iniciar trabajo.
     */
    private function checkHighPriorityIdle(ServiceRequest $request): void
    {
        if (!in_array($request->priority_level, ['P0', 'P1'])) {
            return;
        }

        // Solo aplica si está aceptada pero no en progreso
        if (!in_array($request->status, [ServiceRequest::STATUS_ACCEPTED, ServiceRequest::STATUS_REOPENED])) {
            return;
        }

        $hoursThreshold = $this->config->highPriorityIdleHours();
        $referenceTime = $request->accepted_at ?? $request->created_at;
        $hoursIdle = $referenceTime->diffInHours(now());

        if ($hoursIdle >= $hoursThreshold) {
            $severity = $request->priority_level === 'P0'
                ? OperationalAlert::SEVERITY_CRITICAL
                : OperationalAlert::SEVERITY_HIGH;

            $this->createAlert(
                $request,
                OperationalAlert::TYPE_HIGH_PRIORITY_IDLE,
                $severity,
                "Solicitud {$request->priority_level} ({$request->ticket_number}) sin iniciar ({$hoursIdle}h)",
                [
                    'hours_idle' => $hoursIdle,
                    'threshold_hours' => $hoursThreshold,
                    'priority_level' => $request->priority_level,
                    'accepted_at' => $request->accepted_at?->toISOString(),
                ]
            );
        }
    }

    /**
     * Regla: SLA en riesgo (porcentaje de tiempo consumido).
     */
    private function checkSlaAtRisk(ServiceRequest $request): void
    {
        if (!$request->sla) {
            return;
        }

        // Solo evaluar si no está pausada ni ya breached
        if ($request->status === ServiceRequest::STATUS_PAUSED) {
            return;
        }

        $thresholdPercent = $this->config->slaRiskThresholdPercent();
        $breaches = $this->calculateSlaProgress($request);

        foreach ($breaches as $phase => $info) {
            if ($info['is_completed']) {
                continue; // Ya se cumplió este hito
            }

            if ($info['percent_consumed'] >= 100) {
                continue; // Ya es brecha, se maneja en checkSlaBreached
            }

            if ($info['percent_consumed'] >= $thresholdPercent) {
                $this->createAlert(
                    $request,
                    OperationalAlert::TYPE_SLA_AT_RISK,
                    $this->deriveSeverityFromSlaPhase($phase, $request->priority_level),
                    "SLA de {$info['label']} al {$info['percent_consumed']}% para {$request->ticket_number}",
                    [
                        'phase' => $phase,
                        'percent_consumed' => $info['percent_consumed'],
                        'threshold_percent' => $thresholdPercent,
                        'deadline' => $info['deadline']?->toISOString(),
                        'minutes_remaining' => $info['minutes_remaining'],
                    ]
                );
            }
        }
    }

    /**
     * Regla: SLA incumplido (deadline pasado).
     */
    private function checkSlaBreached(ServiceRequest $request): void
    {
        if (!$request->sla) {
            return;
        }

        if ($request->status === ServiceRequest::STATUS_PAUSED) {
            return;
        }

        $breaches = $this->calculateSlaProgress($request);

        foreach ($breaches as $phase => $info) {
            if ($info['is_completed']) {
                continue;
            }

            if ($info['percent_consumed'] >= 100 && $info['deadline'] && now()->gte($info['deadline'])) {
                $minutesOverdue = (int) $info['deadline']->diffInMinutes(now());

                $this->createAlert(
                    $request,
                    OperationalAlert::TYPE_SLA_BREACHED,
                    $this->deriveSeverityFromPriority($request->priority_level),
                    "SLA de {$info['label']} INCUMPLIDO para {$request->ticket_number} (excede por {$minutesOverdue} min)",
                    [
                        'phase' => $phase,
                        'deadline' => $info['deadline']->toISOString(),
                        'minutes_overdue' => $minutesOverdue,
                        'priority_level' => $request->priority_level,
                    ]
                );
            }
        }
    }

    /**
     * Regla: Solicitud estancada (sin actividad reciente).
     */
    private function checkStaleRequest(ServiceRequest $request): void
    {
        // Solo aplica a solicitudes en progreso, aceptadas o reabiertas
        if (!in_array($request->status, [
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_REOPENED,
        ])) {
            return;
        }

        $priority = $request->priority_level ?? 'P3';
        $daysThreshold = $this->config->staleDaysForPriority($priority);

        $lastActivity = $this->getLastActivityDate($request);
        $daysSinceActivity = $lastActivity->diffInDays(now());

        if ($daysSinceActivity >= $daysThreshold) {
            $this->createAlert(
                $request,
                OperationalAlert::TYPE_STALE_REQUEST,
                $this->deriveSeverityFromPriority($priority),
                "Solicitud {$request->ticket_number} sin actividad por {$daysSinceActivity} días (umbral: {$daysThreshold}d)",
                [
                    'days_inactive' => $daysSinceActivity,
                    'threshold_days' => $daysThreshold,
                    'last_activity_at' => $lastActivity->toISOString(),
                    'priority_level' => $priority,
                ]
            );
        }
    }

    /**
     * Regla: Solicitud pausada por demasiado tiempo.
     */
    private function checkPausedTooLong(ServiceRequest $request): void
    {
        if ($request->status !== ServiceRequest::STATUS_PAUSED || !$request->paused_at) {
            return;
        }

        $maxDays = $this->config->pausedMaxDays();
        $daysPaused = $request->paused_at->diffInDays(now());

        if ($daysPaused >= $maxDays) {
            $this->createAlert(
                $request,
                OperationalAlert::TYPE_PAUSED_TOO_LONG,
                OperationalAlert::SEVERITY_MEDIUM,
                "Solicitud {$request->ticket_number} pausada por {$daysPaused} días (máximo: {$maxDays}d)",
                [
                    'days_paused' => $daysPaused,
                    'threshold_days' => $maxDays,
                    'paused_at' => $request->paused_at->toISOString(),
                    'pause_reason' => $request->pause_reason,
                ]
            );
        }
    }

    /**
     * Regla: Resolución vencida (due_date o resolution_deadline pasados).
     */
    private function checkOverdueResolution(ServiceRequest $request): void
    {
        // Usar resolution_deadline si existe, sino due_date
        $deadline = $request->resolution_deadline ?? ($request->due_date ? Carbon::parse($request->due_date)->endOfDay() : null);

        if (!$deadline) {
            return;
        }

        if ($request->status === ServiceRequest::STATUS_PAUSED) {
            return;
        }

        if (now()->gte($deadline) && !$request->resolved_at) {
            $daysOverdue = (int) $deadline->diffInDays(now());

            $this->createAlert(
                $request,
                OperationalAlert::TYPE_OVERDUE_RESOLUTION,
                $this->deriveSeverityFromPriority($request->priority_level),
                "Solicitud {$request->ticket_number} con resolución vencida ({$daysOverdue} días de exceso)",
                [
                    'deadline' => $deadline->toISOString(),
                    'days_overdue' => $daysOverdue,
                    'priority_level' => $request->priority_level,
                ]
            );
        }
    }

    // ==================== EVALUADORES DE TAREAS ====================

    /**
     * Evaluar tareas bloqueadas.
     */
    private function evaluateBlockedTasks(?int $companyId): void
    {
        $daysThreshold = $this->config->blockedTaskDays();

        $query = Task::where('status', 'blocked')
            ->whereNotNull('blocked_at')
            ->where('blocked_at', '<=', now()->subDays($daysThreshold));

        if ($companyId) {
            $query->whereHas('serviceRequest', function ($q) use ($companyId) {
                $q->withoutGlobalScopes()->where('company_id', $companyId);
            });
        }

        $query->with('serviceRequest')->chunkById(50, function ($tasks) use ($daysThreshold) {
            foreach ($tasks as $task) {
                $daysBlocked = $task->blocked_at->diffInDays(now());

                $this->createAlert(
                    $task,
                    OperationalAlert::TYPE_BLOCKED_TASK,
                    OperationalAlert::SEVERITY_HIGH,
                    "Tarea '{$task->title}' bloqueada por {$daysBlocked} días" .
                        ($task->serviceRequest ? " (SR: {$task->serviceRequest->ticket_number})" : ''),
                    [
                        'days_blocked' => $daysBlocked,
                        'threshold_days' => $daysThreshold,
                        'block_reason' => $task->block_reason,
                        'task_code' => $task->task_code,
                        'service_request_ticket' => $task->serviceRequest?->ticket_number,
                    ]
                );
            }
        });
    }

    // ==================== RESOLUCIÓN AUTOMÁTICA ====================

    /**
     * Resolver alertas cuya condición ya no aplica.
     */
    private function autoResolveStaleAlerts(): void
    {
        // Resolver alertas de solicitudes que ya fueron cerradas/resueltas/canceladas
        $closedStatuses = [
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_CLOSED,
            ServiceRequest::STATUS_CANCELLED,
        ];

        $resolved = OperationalAlert::active()
            ->forServiceRequests()
            ->whereHasMorph('alertable', [ServiceRequest::class], function ($query) use ($closedStatuses) {
                $query->withoutGlobalScopes()->whereIn('status', $closedStatuses);
            })
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Resuelta automáticamente: solicitud cerrada/resuelta/cancelada.',
            ]);

        $this->alertsResolved += $resolved;

        // Resolver alertas de tareas ya no bloqueadas
        $resolvedTasks = OperationalAlert::active()
            ->ofType(OperationalAlert::TYPE_BLOCKED_TASK)
            ->forTasks()
            ->whereHasMorph('alertable', [Task::class], function ($query) {
                $query->where('status', '!=', 'blocked');
            })
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Resuelta automáticamente: tarea ya no está bloqueada.',
            ]);

        $this->alertsResolved += $resolvedTasks;

        // Resolver alertas de aceptación pendiente para SRs que ya fueron aceptadas
        $resolvedAcceptance = OperationalAlert::active()
            ->ofType(OperationalAlert::TYPE_PENDING_ACCEPTANCE)
            ->forServiceRequests()
            ->whereHasMorph('alertable', [ServiceRequest::class], function ($query) {
                $query->withoutGlobalScopes()->where('status', '!=', ServiceRequest::STATUS_PENDING);
            })
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Resuelta automáticamente: solicitud aceptada.',
            ]);

        $this->alertsResolved += $resolvedAcceptance;

        // Resolver alertas de alta prioridad idle para SRs ya en progreso
        $resolvedIdle = OperationalAlert::active()
            ->ofType(OperationalAlert::TYPE_HIGH_PRIORITY_IDLE)
            ->forServiceRequests()
            ->whereHasMorph('alertable', [ServiceRequest::class], function ($query) {
                $query->withoutGlobalScopes()->where('status', ServiceRequest::STATUS_IN_PROGRESS);
            })
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Resuelta automáticamente: solicitud en progreso.',
            ]);

        $this->alertsResolved += $resolvedIdle;

        // Resolver alertas de pausa para SRs que ya no están pausadas
        $resolvedPaused = OperationalAlert::active()
            ->ofType(OperationalAlert::TYPE_PAUSED_TOO_LONG)
            ->forServiceRequests()
            ->whereHasMorph('alertable', [ServiceRequest::class], function ($query) {
                $query->withoutGlobalScopes()->where('status', '!=', ServiceRequest::STATUS_PAUSED);
            })
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Resuelta automáticamente: solicitud reanudada.',
            ]);

        $this->alertsResolved += $resolvedPaused;
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Crear una alerta si no existe una activa del mismo tipo para el recurso.
     */
    private function createAlert($model, string $type, string $severity, string $message, array $metadata = []): void
    {
        $alertableType = get_class($model);
        $alertableId = $model->id;

        if (OperationalAlert::existsActiveFor($alertableType, $alertableId, $type)) {
            return;
        }

        OperationalAlert::create([
            'alertable_type' => $alertableType,
            'alertable_id' => $alertableId,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => OperationalAlert::$alertTypes[$type]['label'] ?? $type,
            'message' => $message,
            'metadata' => $metadata,
            'alert_at' => now(),
        ]);

        $this->alertsGenerated++;
        $this->summary[$type] = ($this->summary[$type] ?? 0) + 1;
    }

    /**
     * Calcular el progreso de cumplimiento SLA por fases.
     */
    private function calculateSlaProgress(ServiceRequest $request): array
    {
        $sla = $request->sla;
        $phases = [];

        // Fase de aceptación
        if ($sla->acceptance_time_minutes > 0) {
            $deadline = $request->acceptance_deadline;
            $totalMinutes = $sla->acceptance_time_minutes;
            $elapsed = $request->created_at->diffInMinutes(now());
            $pausedMinutes = $request->status === ServiceRequest::STATUS_PAUSED ? ($request->total_paused_minutes ?? 0) : 0;
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            $phases['acceptance'] = [
                'label' => 'aceptación',
                'is_completed' => !is_null($request->accepted_at),
                'deadline' => $deadline,
                'total_minutes' => $totalMinutes,
                'elapsed_minutes' => $effectiveElapsed,
                'percent_consumed' => $totalMinutes > 0 ? round(($effectiveElapsed / $totalMinutes) * 100, 1) : 0,
                'minutes_remaining' => max(0, $totalMinutes - $effectiveElapsed),
            ];
        }

        // Fase de respuesta
        if ($sla->response_time_minutes > 0) {
            $deadline = $request->response_deadline;
            $totalMinutes = $sla->response_time_minutes;
            $startFrom = $request->accepted_at ?? $request->created_at;
            $elapsed = $startFrom->diffInMinutes(now());
            $pausedMinutes = $request->total_paused_minutes ?? 0;
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            $phases['response'] = [
                'label' => 'respuesta',
                'is_completed' => !is_null($request->responded_at),
                'deadline' => $deadline,
                'total_minutes' => $totalMinutes,
                'elapsed_minutes' => $effectiveElapsed,
                'percent_consumed' => $totalMinutes > 0 ? round(($effectiveElapsed / $totalMinutes) * 100, 1) : 0,
                'minutes_remaining' => max(0, $totalMinutes - $effectiveElapsed),
            ];
        }

        // Fase de resolución
        if ($sla->resolution_time_minutes > 0) {
            $deadline = $request->resolution_deadline;
            $totalMinutes = $sla->resolution_time_minutes;
            $startFrom = $request->responded_at ?? $request->accepted_at ?? $request->created_at;
            $elapsed = $startFrom->diffInMinutes(now());
            $pausedMinutes = $request->total_paused_minutes ?? 0;
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            $phases['resolution'] = [
                'label' => 'resolución',
                'is_completed' => !is_null($request->resolved_at),
                'deadline' => $deadline,
                'total_minutes' => $totalMinutes,
                'elapsed_minutes' => $effectiveElapsed,
                'percent_consumed' => $totalMinutes > 0 ? round(($effectiveElapsed / $totalMinutes) * 100, 1) : 0,
                'minutes_remaining' => max(0, $totalMinutes - $effectiveElapsed),
            ];
        }

        return $phases;
    }

    /**
     * Obtener la fecha de la última actividad de una solicitud.
     *
     * Se considera actividad:
     * - Último cambio de estado (status history)
     * - Última evidencia/nota agregada
     * - Última actualización del modelo
     */
    private function getLastActivityDate(ServiceRequest $request): Carbon
    {
        $dates = collect([
            $request->updated_at,
        ]);

        // Última evidencia o nota
        $lastEvidence = $request->evidences->max('created_at');
        if ($lastEvidence) {
            $dates->push(Carbon::parse($lastEvidence));
        }

        // Timestamps de workflow
        $workflowDates = array_filter([
            $request->accepted_at,
            $request->responded_at,
            $request->paused_at,
            $request->resumed_at,
            $request->technician_assigned_at,
        ]);

        foreach ($workflowDates as $date) {
            $dates->push(Carbon::parse($date));
        }

        return $dates->filter()->max() ?? $request->created_at;
    }

    /**
     * Derivar severidad a partir del nivel de prioridad de la solicitud.
     */
    private function deriveSeverityFromPriority(?string $priorityLevel): string
    {
        return match ($priorityLevel) {
            'P0' => OperationalAlert::SEVERITY_CRITICAL,
            'P1' => OperationalAlert::SEVERITY_HIGH,
            'P2' => OperationalAlert::SEVERITY_MEDIUM,
            default => OperationalAlert::SEVERITY_LOW,
        };
    }

    /**
     * Derivar severidad a partir de la fase SLA y prioridad.
     */
    private function deriveSeverityFromSlaPhase(string $phase, ?string $priorityLevel): string
    {
        // La fase de aceptación es más urgente que respuesta/resolución
        if ($phase === 'acceptance') {
            return in_array($priorityLevel, ['P0', 'P1'])
                ? OperationalAlert::SEVERITY_HIGH
                : OperationalAlert::SEVERITY_MEDIUM;
        }

        return $this->deriveSeverityFromPriority($priorityLevel);
    }

    // ==================== MÉTODOS PÚBLICOS DE CONSULTA ====================

    /**
     * Obtener resumen de alertas activas agrupadas por tipo.
     */
    public function getActiveSummary(?int $companyId = null): array
    {
        $query = OperationalAlert::active();

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where(function ($sub) use ($companyId) {
                    $sub->where('alertable_type', ServiceRequest::class)
                        ->whereHasMorph('alertable', [ServiceRequest::class], function ($srQuery) use ($companyId) {
                            $srQuery->withoutGlobalScopes()->where('company_id', $companyId);
                        });
                })->orWhere(function ($sub) use ($companyId) {
                    $sub->where('alertable_type', Task::class)
                        ->whereHasMorph('alertable', [Task::class], function ($taskQuery) use ($companyId) {
                            $taskQuery->whereHas('serviceRequest', function ($srQuery) use ($companyId) {
                                $srQuery->withoutGlobalScopes()->where('company_id', $companyId);
                            });
                        });
                });
            });
        }

        return [
            'total' => (clone $query)->count(),
            'by_severity' => [
                'critica' => (clone $query)->critical()->count(),
                'alta' => (clone $query)->ofSeverity(OperationalAlert::SEVERITY_HIGH)->count(),
                'media' => (clone $query)->ofSeverity(OperationalAlert::SEVERITY_MEDIUM)->count(),
                'baja' => (clone $query)->ofSeverity(OperationalAlert::SEVERITY_LOW)->count(),
            ],
            'by_type' => (clone $query)->select('alert_type', DB::raw('count(*) as total'))
                ->groupBy('alert_type')
                ->pluck('total', 'alert_type')
                ->toArray(),
            'unread' => (clone $query)->unread()->count(),
        ];
    }
}
