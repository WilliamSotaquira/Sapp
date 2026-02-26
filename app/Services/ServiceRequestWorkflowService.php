<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceRequestWorkflowService
{
    /**
     * Aceptar solicitud con transacción
     */
    public function acceptRequest(ServiceRequest $serviceRequest): array
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return [
                'success' => false,
                'message' => 'Esta solicitud ya no puede ser aceptada. Estado actual: ' . $serviceRequest->status
            ];
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $serviceRequest->update([
                    'status' => 'ACEPTADA',
                    'accepted_at' => now(),
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Solicitud Aceptada',
                    'description' => 'La solicitud fue aceptada por ' . auth()->user()->name,
                    'action' => 'ACCEPTED',
                    'previous_status' => 'PENDIENTE',
                    'new_status' => 'ACEPTADA',
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud aceptada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al aceptar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al aceptar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Rechazar solicitud
     */
    public function rejectRequest(ServiceRequest $serviceRequest, string $rejectionReason): array
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return [
                'success' => false,
                'message' => 'La solicitud debe estar en estado PENDIENTE para ser rechazada.'
            ];
        }

        try {
            ServiceRequest::withoutEvents(function () use ($serviceRequest, $rejectionReason) {
                $serviceRequest->update([
                    'status' => 'RECHAZADA',
                    'rejection_reason' => $rejectionReason,
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id(),
                ]);
            });

            $this->createSystemEvidence($serviceRequest, [
                'title' => 'Solicitud Rechazada',
                'description' => $rejectionReason,
                'action' => 'REJECTED',
                'previous_status' => 'PENDIENTE',
                'new_status' => 'RECHAZADA',
                'rejection_reason' => $rejectionReason,
            ]);

            return ['success' => true, 'message' => 'Solicitud rechazada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al rechazar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Iniciar procesamiento
     */
    public function startProcessing(ServiceRequest $serviceRequest, bool $useStandardTasks = false): array
    {
        if ($serviceRequest->status !== 'ACEPTADA') {
            return ['success' => false, 'message' => 'La solicitud debe estar ACEPTADA para iniciar.'];
        }

        if (!$serviceRequest->assigned_to) {
            return ['success' => false, 'message' => 'Asigna un técnico antes de iniciar.'];
        }

        try {
            DB::transaction(function () use ($serviceRequest, $useStandardTasks) {
                $previousStatus = $serviceRequest->status;

                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'responded_at' => now(),
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Procesamiento Iniciado',
                    'description' => "Inicio de trabajo - Técnico: {$serviceRequest->assignee->name}",
                    'action' => 'STARTED',
                    'previous_status' => $previousStatus,
                    'new_status' => 'EN_PROCESO',
                    'assigned_technician' => $serviceRequest->assigned_to,
                ]);

                // Crear tareas predefinidas solo si el usuario lo solicitó
                if ($useStandardTasks) {
                    $this->createStandardTasksForRequest($serviceRequest);
                }
            });

            $message = 'Solicitud marcada como en proceso.';
            if ($useStandardTasks) {
                $message .= ' Tareas predefinidas creadas y asignadas.';
            } else {
                $message .= ' Puedes crear las tareas manualmente.';
            }

            return ['success' => true, 'message' => $message];
        } catch (\Exception $e) {
            Log::error('Error al iniciar procesamiento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al iniciar el procesamiento.'];
        }
    }

    /**
     * Resolver solicitud
     */
    public function resolveRequest(ServiceRequest $serviceRequest, array $data = []): array
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return ['success' => false, 'message' => 'La solicitud debe estar en estado EN PROCESO.'];
        }

        try {
            ServiceRequest::withoutEvents(function () use ($serviceRequest, $data) {
                $serviceRequest->update([
                    'status' => 'RESUELTA',
                    'resolution_notes' => $data['resolution_notes'] ?? 'Resolución completada',
                    'actual_resolution_time' => $data['actual_resolution_time'] ?? 60,
                    'resolved_at' => now(),
                ]);
            });

            return ['success' => true, 'message' => '¡Solicitud resuelta correctamente!'];
        } catch (\Exception $e) {
            Log::error('Error al resolver solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Pausar solicitud
     */
    public function pauseRequest(ServiceRequest $serviceRequest, string $pauseReason): array
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return ['success' => false, 'message' => 'Solo se pueden pausar solicitudes en proceso.'];
        }

        if ($serviceRequest->is_paused) {
            return ['success' => false, 'message' => 'La solicitud ya está pausada.'];
        }

        try {
            DB::transaction(function () use ($serviceRequest, $pauseReason) {
                $serviceRequest->update([
                    'status' => 'PAUSADA',
                    'paused_at' => now(),
                    'is_paused' => true,
                    'pause_reason' => $pauseReason,
                    'paused_by' => auth()->id(),
                    'total_paused_minutes' => $serviceRequest->total_paused_minutes ?? 0,
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Solicitud Pausada',
                    'description' => $pauseReason,
                    'action' => 'PAUSED',
                    'previous_status' => 'EN_PROCESO',
                    'new_status' => 'PAUSADA',
                    'pause_reason' => $pauseReason,
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud pausada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al pausar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al pausar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Reanudar solicitud pausada
     */
    public function resumeRequest(ServiceRequest $serviceRequest): array
    {
        if ($serviceRequest->status !== 'PAUSADA') {
            return ['success' => false, 'message' => 'Solo se pueden reanudar solicitudes pausadas.'];
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $currentPauseMinutes = 0;
                if ($serviceRequest->paused_at) {
                    $currentPauseMinutes = $serviceRequest->paused_at->diffInMinutes(now());
                }

                $totalPausedMinutes = ($serviceRequest->total_paused_minutes ?? 0) + $currentPauseMinutes;

                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'is_paused' => false,
                    'resumed_at' => now(),
                    'pause_reason' => null,
                    'paused_by' => null,
                    'total_paused_minutes' => $totalPausedMinutes,
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Solicitud Reanudada',
                    'description' => "La solicitud fue reanudada después de {$currentPauseMinutes} minutos en pausa.",
                    'action' => 'RESUMED',
                    'previous_status' => 'PAUSADA',
                    'new_status' => 'EN_PROCESO',
                    'pause_duration_minutes' => $currentPauseMinutes,
                    'total_pause_minutes' => $totalPausedMinutes,
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud reanudada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al reanudar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al reanudar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Crear evidencia del sistema de forma consistente
     */
    private function createSystemEvidence(ServiceRequest $serviceRequest, array $data): void
    {
        ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'evidence_type' => 'SISTEMA',
            'created_by' => auth()->id(),
            'evidence_data' => array_merge([
                'action' => $data['action'],
                'performed_by' => auth()->id(),
                'performed_at' => now()->toISOString(),
            ], $data),
        ]);
    }

    /**
     * Crear tareas predefinidas para una solicitud
     */
    private function createStandardTasksForRequest(ServiceRequest $serviceRequest): void
    {
        $standardTasks = $serviceRequest->subService
            ->standardTasks()
            ->active()
            ->ordered()
            ->get();

        // Determinar si hay técnico asignado para auto-asignar al calendario
        $hasTechnician = !empty($serviceRequest->assigned_to);
        $technicianId = null;

        if ($hasTechnician) {
            $assignedUser = \App\Models\User::find($serviceRequest->assigned_to);
            $technicianId = $assignedUser?->technician?->id;
        }

        foreach ($standardTasks as $standardTask) {
            // Generar información del código antes del lock
            $date = now()->addDay();
            $prefix = $standardTask->type === 'impact' ? 'IMP' : 'REG';
            $dateStr = $date->format('Ymd');
            $lockName = "task_code_gen_{$prefix}_{$dateStr}";

            // Obtener lock de aplicación (espera hasta 10 segundos)
            $lockAcquired = \DB::select("SELECT GET_LOCK(?, 10) as result", [$lockName])[0]->result;

            if (!$lockAcquired) {
                \Log::error("Failed to acquire lock for task code generation", [
                    'standard_task_id' => $standardTask->id,
                    'service_request_id' => $serviceRequest->id,
                    'lock_name' => $lockName
                ]);
                continue;
            }

            try {
                $taskCode = \DB::transaction(function () use ($standardTask, $serviceRequest, $prefix, $dateStr, $date, $technicianId, $hasTechnician) {
                    // Obtener último código con lock de fila (incluir borrados)
                    $lastTask = \App\Models\Task::withTrashed()
                        ->where('task_code', 'like', "{$prefix}-{$dateStr}-%")
                        ->lockForUpdate()
                        ->orderBy('task_code', 'desc')
                        ->first();

                    $sequence = 1;
                    if ($lastTask) {
                        $parts = explode('-', $lastTask->task_code);
                        $sequence = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
                    }

                    $taskCode = sprintf('%s-%s-%03d', $prefix, $dateStr, $sequence);

                    // Determinar el status: si hay técnico y service_request, auto-confirmar
                    $taskStatus = ($hasTechnician && $technicianId) ? 'confirmed' : 'pending';

                    // Preparar datos para inserción
                    $taskData = [
                        'type' => $standardTask->type,
                        'title' => $standardTask->title,
                        'description' => $standardTask->description,
                        'service_request_id' => $serviceRequest->id,
                        'technician_id' => $technicianId,
                        'scheduled_date' => $date,
                        'scheduled_start_time' => '08:00',
                        'estimated_hours' => $standardTask->estimated_hours,
                        'priority' => $standardTask->priority,
                        'status' => $taskStatus,
                        'technical_complexity' => $standardTask->technical_complexity,
                        'technologies' => $standardTask->technologies,
                        'required_accesses' => $standardTask->required_accesses,
                        'environment' => $standardTask->environment,
                        'technical_notes' => $standardTask->technical_notes,
                        'task_code' => $taskCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insertar directamente sin eventos del modelo
                    \DB::table('tasks')->insert($taskData);

                    // Retornar el código para buscar después
                    return $taskCode;
                });

                // Obtener la tarea recién creada (después del commit)
                $task = \App\Models\Task::where('task_code', $taskCode)->first();

                // Liberar lock DESPUÉS del commit
                \DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
            } catch (\Exception $e) {
                // Asegurar que el lock se libera incluso si hay error
                \DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);

                \Log::error("Failed to create task from standard task", [
                    'standard_task_id' => $standardTask->id,
                    'service_request_id' => $serviceRequest->id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }

            // Si no se pudo crear la tarea, continuar con la siguiente
            if (!isset($task) || !$task) {
                continue;
            }

            // Registrar en el historial
            $notes = 'Tarea predefinida creada automáticamente desde solicitud de servicio';
            if ($hasTechnician && $technicianId) {
                $notes .= ' y asignada automáticamente a la agenda del técnico';
            }

            \App\Models\TaskHistory::create([
                'task_id' => $task->id,
                'action' => 'created',
                'user_id' => auth()->id(),
                'notes' => $notes,
                'metadata' => [
                    'standard_task_id' => $standardTask->id,
                    'type' => $task->type,
                    'priority' => $task->priority,
                    'auto_assigned_to_calendar' => $hasTechnician && $technicianId
                ]
            ]);

            // Si hay técnico asignado, registrar asignación
            if ($technicianId) {
                \App\Models\TaskHistory::create([
                    'task_id' => $task->id,
                    'action' => 'assigned',
                    'user_id' => auth()->id(),
                    'notes' => 'Técnico asignado automáticamente desde solicitud de servicio',
                    'metadata' => ['technician_id' => $technicianId]
                ]);
            }

            // Asociar SLA de la solicitud
            if ($serviceRequest->sla_id) {
                $task->update(['sla_id' => $serviceRequest->sla_id]);
            }

            // Crear las subtareas si existen
            foreach ($standardTask->standardSubtasks as $standardSubtask) {
                $task->subtasks()->create([
                    'title' => $standardSubtask->title,
                    'description' => $standardSubtask->description,
                    'priority' => $standardSubtask->priority,
                    'status' => 'pending',
                    'is_completed' => false,
                ]);
            }
        }
    }
}
