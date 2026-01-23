<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Models\SubService;
use App\Models\StandardTask;
use App\Models\Task;
use App\Models\User;
use App\Models\Cut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceRequestService
{
    private function calculateEstimatedMinutesFromSubtasks(array $subtasks): int
    {
        $totalMinutes = 0;

        foreach ($subtasks as $subtask) {
            if (!is_array($subtask)) {
                continue;
            }

            $minutesRaw = $subtask['estimated_minutes'] ?? null;
            if ($minutesRaw === null || $minutesRaw === '') {
                $minutes = 25;
            } else {
                $minutes = (int) $minutesRaw;
            }

            if ($minutes > 0) {
                $totalMinutes += $minutes;
            }
        }

        return $totalMinutes;
    }

    private function calculateEstimatedHoursFromSubtasks(array $subtasks): ?float
    {
        $totalMinutes = $this->calculateEstimatedMinutesFromSubtasks($subtasks);
        if ($totalMinutes <= 0) {
            return null;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Construir query base con los filtros aplicados
     */
    private function buildFilteredQuery(array $filters = [])
    {
        $query = ServiceRequest::query();

        // Búsqueda general
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('requester', function ($rq) use ($search) {
                        $rq->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Estado / abiertas
        if (!empty($filters['open'])) {
            $query->whereNotIn('status', ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA']);
        } elseif (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Criticidad
        if (!empty($filters['criticality'])) {
            $query->where('criticality_level', $filters['criticality']);
        }

        // Servicio
        if (!empty($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            if ($serviceId > 0) {
                $query->whereHas('subService.service', function ($q) use ($serviceId) {
                    $q->where('id', $serviceId);
                });
            }
        }

        // Solicitante (nombre o email parcial)
        if (!empty($filters['requester'])) {
            $term = trim($filters['requester']);
            $query->whereHas('requester', function($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        // Rango de fechas (creación)
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        if ($startDate || $endDate) {
            try {
                $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : null;
            } catch (\Exception $e) { $start = null; }
            try {
                $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : null;
            } catch (\Exception $e) { $end = null; }

            if ($start && $end) {
                $query->whereBetween('created_at', [$start, $end]);
            } elseif ($start) {
                $query->where('created_at', '>=', $start);
            } elseif ($end) {
                $query->where('created_at', '<=', $end);
            }
        }

        return $query;
    }

    /**
     * Obtener solicitudes con filtros y paginación optimizada
     */
    public function getFilteredServiceRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters)
            ->with([
                'subService:id,name,service_id',
                'subService.service:id,name,service_family_id',
                'subService.service.family:id,name',
                'requester:id,name,email'
            ])
            ->select([
                'id', 'ticket_number', 'title', 'description', 'status',
                'criticality_level', 'requester_id', 'sub_service_id',
                'created_at', 'updated_at'
            ]);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Obtener estadísticas del dashboard optimizada
     */
    public function getDashboardStats(): array
    {
        // Una sola consulta para obtener todas las estadísticas
        $stats = ServiceRequest::selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN status = 'PENDIENTE' THEN 1 END) as pending_count,
            COUNT(CASE WHEN criticality_level = 'CRITICA' THEN 1 END) as critical_count,
            COUNT(CASE WHEN status = 'RESUELTA' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN status = 'CERRADA' THEN 1 END) as closed_count,
            COUNT(CASE WHEN status IN ('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA') THEN 1 END) as open_count
        ")->first();

        return [
            'totalCount' => $stats->total_count ?? 0,
            'pendingCount' => $stats->pending_count ?? 0,
            'criticalCount' => $stats->critical_count ?? 0,
            'resolvedCount' => $stats->resolved_count ?? 0,
            'closedCount' => $stats->closed_count ?? 0,
            'openCount' => $stats->open_count ?? 0,
        ];
    }

    /**
     * Obtener estadísticas basadas en los mismos filtros del listado
     */
    public function getFilteredStats(array $filters = []): array
    {
        $query = $this->buildFilteredQuery($filters);

        $stats = $query->selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN status = 'PENDIENTE' THEN 1 END) as pending_count,
            COUNT(CASE WHEN criticality_level = 'CRITICA' THEN 1 END) as critical_count,
            COUNT(CASE WHEN status = 'RESUELTA' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN status = 'CERRADA' THEN 1 END) as closed_count,
            COUNT(CASE WHEN status IN ('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA') THEN 1 END) as open_count
        ")->first();

        return [
            'totalCount' => $stats->total_count ?? 0,
            'pendingCount' => $stats->pending_count ?? 0,
            'criticalCount' => $stats->critical_count ?? 0,
            'resolvedCount' => $stats->resolved_count ?? 0,
            'closedCount' => $stats->closed_count ?? 0,
            'openCount' => $stats->open_count ?? 0,
        ];
    }

    /**
     * Crear nueva solicitud de servicio
     */
    public function createServiceRequest(array $data): ServiceRequest
    {
        Log::info('=== CREANDO NUEVA SOLICITUD ===', ['data' => $data]);

        try {
            $tasks = $data['tasks'] ?? null;
            $tasksTemplate = $data['tasks_template'] ?? null;
            $cutId = $data['cut_id'] ?? null;

            unset($data['tasks'], $data['tasks_template'], $data['cut_id']);

            // Procesar web_routes si existe
            if (!empty($data['web_routes'])) {
                $data['web_routes'] = is_string($data['web_routes'])
                    ? json_decode($data['web_routes'], true) ?? []
                    : $data['web_routes'];
            }

            $serviceRequest = DB::transaction(function () use ($data, $tasks, $tasksTemplate, $cutId) {
                $serviceRequest = ServiceRequest::create($data);

                // Vincular al corte si se proporcionó
                if (!empty($cutId)) {
                    $serviceRequest->cuts()->attach($cutId);
                }

                $this->createOptionalTasksForRequest($serviceRequest, $tasks, $tasksTemplate);

                return $serviceRequest;
            });

            Log::info('✅ Solicitud creada exitosamente', [
                'id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
                'requester_id' => $serviceRequest->requester_id,
                'cut_id' => $cutId,
            ]);

            return $serviceRequest;
        } catch (\Exception $e) {
            Log::error('❌ Error al crear solicitud: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    private function createOptionalTasksForRequest(ServiceRequest $serviceRequest, ?array $tasks, ?string $tasksTemplate): void
    {
        $tasks = is_array($tasks) ? $tasks : [];

        $normalized = [];
        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $title = trim((string)($task['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $subtasksNormalized = (function () use ($task) {
                $subtasks = $task['subtasks'] ?? null;
                if (!is_array($subtasks)) {
                    return [];
                }

                $out = [];
                foreach ($subtasks as $subtask) {
                    if (!is_array($subtask)) {
                        continue;
                    }

                    $stTitle = trim((string)($subtask['title'] ?? ''));
                    if ($stTitle === '') {
                        continue;
                    }

                    $out[] = [
                        'title' => $stTitle,
                        'notes' => trim((string)($subtask['notes'] ?? '')) ?: null,
                        'priority' => in_array(($subtask['priority'] ?? 'medium'), ['high', 'medium', 'low'], true)
                            ? $subtask['priority']
                            : 'medium',
                        'estimated_minutes' => isset($subtask['estimated_minutes']) && $subtask['estimated_minutes'] !== ''
                            ? (int) $subtask['estimated_minutes']
                            : null,
                    ];
                }

                return $out;
            })();

            $minutesFromSubtasks = $this->calculateEstimatedMinutesFromSubtasks($subtasksNormalized);
            $manualMinutes = null;
            if (array_key_exists('estimated_minutes', $task) && $task['estimated_minutes'] !== '') {
                $manualMinutes = max(0, (int) $task['estimated_minutes']);
            } elseif (array_key_exists('estimated_hours', $task) && $task['estimated_hours'] !== '') {
                $manualMinutes = max(0, (int) round(((float) $task['estimated_hours']) * 60));
            }

            if ($minutesFromSubtasks > 0) {
                $estimatedHours = round($minutesFromSubtasks / 60, 2);
            } elseif ($manualMinutes !== null) {
                $estimatedHours = round($manualMinutes / 60, 2);
            } else {
                $estimatedHours = isset($task['estimated_hours']) && $task['estimated_hours'] !== ''
                    ? (float) $task['estimated_hours']
                    : null;
            }

            $normalized[] = [
                'title' => $title,
                'description' => trim((string)($task['description'] ?? '')) ?: null,
                'type' => ($task['type'] ?? 'regular') === 'impact' ? 'impact' : 'regular',
                'priority' => in_array(($task['priority'] ?? 'medium'), ['urgent', 'high', 'medium', 'low'], true)
                    ? $task['priority']
                    : 'medium',
                'estimated_hours' => $estimatedHours,
                'estimate_mode' => 'manual',
                'standard_task_id' => isset($task['standard_task_id']) && $task['standard_task_id'] !== ''
                    ? (int) $task['standard_task_id']
                    : null,
                'subtasks' => $subtasksNormalized,
            ];
        }

        // Fallback: si el usuario eligió plantilla y no llegaron tasks[] (JS deshabilitado)
        if (empty($normalized) && $tasksTemplate === 'subservice_standard') {
            $standardTasks = StandardTask::query()
                ->with('standardSubtasks')
                ->where('sub_service_id', $serviceRequest->sub_service_id)
                ->active()
                ->ordered()
                ->get();

            foreach ($standardTasks as $st) {
                $fallbackSubtasks = $st->standardSubtasks
                    ? $st->standardSubtasks->map(function ($sst) {
                        return [
                            'title' => $sst->title,
                            'notes' => $sst->description ?: null,
                            'priority' => in_array($sst->priority, ['high', 'medium', 'low'], true) ? $sst->priority : 'medium',
                            // Sin estimated_minutes para usar el default del modelo (25)
                            'estimated_minutes' => null,
                        ];
                    })->values()->all()
                    : [];

                $autoEstimated = $this->calculateEstimatedHoursFromSubtasks($fallbackSubtasks);

                $normalized[] = [
                    'title' => $st->title,
                    'description' => $st->description,
                    'type' => $st->type === 'impact' ? 'impact' : 'regular',
                    'priority' => in_array($st->priority, ['urgent', 'high', 'medium', 'low'], true) ? $st->priority : 'medium',
                    'estimated_hours' => $autoEstimated ?? $st->estimated_hours,
                    'estimate_mode' => 'manual',
                    'standard_task_id' => $st->id,
                    'technical_complexity' => $st->technical_complexity,
                    'technologies' => $st->technologies,
                    'required_accesses' => $st->required_accesses,
                    'environment' => $st->environment,
                    'technical_notes' => $st->technical_notes,
                    'subtasks' => $fallbackSubtasks,
                ];
            }
        }

        if (empty($normalized)) {
            return;
        }

        foreach ($normalized as $taskData) {
            $task = Task::create([
                'service_request_id' => $serviceRequest->id,
                'standard_task_id' => $taskData['standard_task_id'] ?? null,
                'type' => $taskData['type'] ?? 'regular',
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'priority' => $taskData['priority'] ?? 'medium',
                'status' => 'pending',
                'estimated_hours' => $taskData['estimated_hours'] ?? null,
                'technical_complexity' => $taskData['technical_complexity'] ?? 3,
                'technologies' => $taskData['technologies'] ?? null,
                'required_accesses' => $taskData['required_accesses'] ?? null,
                'environment' => $taskData['environment'] ?? null,
                'technical_notes' => $taskData['technical_notes'] ?? null,
            ]);

            $subtasks = $taskData['subtasks'] ?? [];
            if (is_array($subtasks) && !empty($subtasks)) {
                $order = 0;
                foreach ($subtasks as $subtaskData) {
                    if (!is_array($subtaskData)) {
                        continue;
                    }

                    $stTitle = trim((string)($subtaskData['title'] ?? ''));
                    if ($stTitle === '') {
                        continue;
                    }

                    $create = [
                        'title' => $stTitle,
                        'notes' => isset($subtaskData['notes']) ? (trim((string)$subtaskData['notes']) ?: null) : null,
                        'priority' => in_array(($subtaskData['priority'] ?? 'medium'), ['high', 'medium', 'low'], true)
                            ? $subtaskData['priority']
                            : 'medium',
                        'order' => $order,
                    ];

                    if (isset($subtaskData['estimated_minutes']) && $subtaskData['estimated_minutes'] !== null && $subtaskData['estimated_minutes'] !== '') {
                        $create['estimated_minutes'] = (int) $subtaskData['estimated_minutes'];
                    }

                    $task->subtasks()->create($create);
                    $order++;
                }
            }
        }
    }

    /**
     * Obtener datos para el formulario de creación
     */
    public function getCreateFormData(?int $selectedSubServiceId = null): array
    {
        $selectedSubService = null;
        if ($selectedSubServiceId) {
            $selectedSubService = SubService::with(['service.family', 'slas'])
                ->where('is_active', true)
                ->find($selectedSubServiceId);
        }

        return [
            // Se deja vacío para usar Select2 AJAX y evitar enviar listas enormes.
            'subServices' => collect(),
            'selectedSubService' => $selectedSubService,
            'requesters' => \App\Models\Requester::active()->orderBy('name')->get(),
            'cuts' => Cut::orderBy('start_date', 'desc')->get(['id', 'name', 'start_date', 'end_date']),
            'criticalityLevels' => ['BAJA', 'MEDIA', 'ALTA', 'URGENTE']
        ];
    }

    /**
     * Cargar solicitud con relaciones optimizadas
     */
    public function loadServiceRequestForShow(ServiceRequest $serviceRequest): ServiceRequest
    {
        return $serviceRequest->load([
            'subService:id,name,service_id',
            'subService.service:id,name,service_family_id',
            'subService.service.family:id,name',
            'sla:id,name,criticality_level,response_time_minutes,resolution_time_minutes',
            'requester:id,name,email,phone',
            'assignee:id,name,email',
            'breachLogs:id,service_request_id,breach_type,breach_minutes,created_at',
            'evidences' => function($query) {
                $query->with('user:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(50); // Limitar evidencias para mejor performance
            }
        ]);
    }

    /**
     * Obtener datos para el formulario de edición
     */
    public function getEditFormData(?int $selectedSubServiceId = null): array
    {
        $selectedSubService = null;
        if ($selectedSubServiceId) {
            $selectedSubService = SubService::with(['service.family', 'slas'])
                ->where('is_active', true)
                ->find($selectedSubServiceId);
        }

        // Se deja vacío para usar Select2 AJAX y evitar enviar listas enormes.
        $subServices = collect();

        $users = User::select(['id', 'name', 'email'])->orderBy('name')->get();
        $requesters = \App\Models\Requester::active()->orderBy('name')->get();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return compact('subServices', 'selectedSubService', 'users', 'requesters', 'criticalityLevels');
    }

    /**
     * Actualizar solicitud de servicio
     */
    public function updateServiceRequest(ServiceRequest $serviceRequest, array $data): ServiceRequest
    {
        Log::info('=== ACTUALIZANDO SOLICITUD ===', [
            'id' => $serviceRequest->id,
            'data' => $data
        ]);

        try {
            $serviceRequest->update($data);

            Log::info('✅ Solicitud actualizada exitosamente', [
                'id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
            ]);

            return $serviceRequest;
        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar solicitud de servicio
     */
    public function deleteServiceRequest(ServiceRequest $serviceRequest): bool
    {
        Log::info('=== ELIMINANDO SOLICITUD ===', [
            'id' => $serviceRequest->id,
            'ticket_number' => $serviceRequest->ticket_number,
        ]);

        try {
            $deleted = $serviceRequest->delete();

            Log::info('✅ Solicitud eliminada exitosamente', [
                'id' => $serviceRequest->id,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar solicitud: ' . $e->getMessage());
            throw $e;
        }
    }
}
