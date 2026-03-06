<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Models\SubService;
use App\Models\StandardTask;
use App\Models\Task;
use App\Models\Technician;
use App\Models\User;
use App\Models\Cut;
use App\Models\Requester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceRequestService
{
    /**
     * Busca o crea solicitante para una entidad.
     * Prioriza email cuando viene informado; si no, usa nombre + entidad.
     */
    public function findOrCreateRequesterForCompany(
        int $companyId,
        string $name,
        ?string $email = null,
        ?string $department = null,
        ?string $position = null
    ): int {
        $cleanName = trim($name);
        $cleanEmail = $email !== null ? trim($email) : null;
        $cleanEmail = $cleanEmail !== '' ? mb_strtolower($cleanEmail) : null;

        if ($cleanName === '') {
            throw new \InvalidArgumentException('El nombre del solicitante es obligatorio.');
        }

        $query = Requester::withoutGlobalScopes()->where('company_id', $companyId);
        if ($cleanEmail) {
            $query->where('email', $cleanEmail);
        } else {
            $query->where('name', $cleanName);
        }

        $requester = $query->first();
        if ($requester) {
            $requester->is_active = true;

            if ($cleanEmail && empty($requester->email)) {
                $requester->email = $cleanEmail;
            }
            if (!empty($department) && empty($requester->department)) {
                $requester->department = trim($department);
            }
            if (!empty($position) && empty($requester->position)) {
                $requester->position = trim($position);
            }

            $requester->save();
            return (int) $requester->id;
        }

        $created = Requester::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'name' => $cleanName,
            'email' => $cleanEmail,
            'department' => $department ? trim($department) : null,
            'position' => $position ? trim($position) : null,
            'is_active' => true,
        ]);

        return (int) $created->id;
    }

    /**
     * Resuelve en una sola consulta los IDs técnicos para crear una solicitud.
     * Retorna: family_id, service_id, sub_service_id, sla_id y cut_id (más reciente).
     */
    public function resolveCreationContext(int $companyId, int $subServiceId, string $criticality): array
    {
        $criticality = mb_strtoupper(trim($criticality));

        $row = DB::table('sub_services as ss')
            ->join('services as s', 's.id', '=', 'ss.service_id')
            ->join('service_families as sf', 'sf.id', '=', 's.service_family_id')
            ->join('companies as c', function ($join) use ($companyId) {
                $join->on('c.active_contract_id', '=', 'sf.contract_id')
                    ->where('c.id', '=', $companyId);
            })
            ->leftJoin('service_subservices as sss', function ($join) {
                $join->on('sss.sub_service_id', '=', 'ss.id')
                    ->on('sss.service_family_id', '=', 'sf.id')
                    ->where('sss.is_active', '=', 1);
            })
            ->leftJoin('service_level_agreements as sla_ss', function ($join) use ($criticality) {
                $join->on('sla_ss.service_subservice_id', '=', 'sss.id')
                    ->where('sla_ss.is_active', '=', 1)
                    ->where('sla_ss.criticality_level', '=', $criticality);
            })
            ->leftJoin('service_level_agreements as sla_sf', function ($join) use ($criticality) {
                $join->on('sla_sf.service_family_id', '=', 'sf.id')
                    ->where('sla_sf.is_active', '=', 1)
                    ->where('sla_sf.criticality_level', '=', $criticality);
            })
            ->where('ss.id', $subServiceId)
            ->where('ss.is_active', 1)
            ->where('s.is_active', 1)
            ->where('sf.is_active', 1)
            ->select([
                'sf.id as family_id',
                's.id as service_id',
                'ss.id as sub_service_id',
                DB::raw('COALESCE(MIN(sla_ss.id), MIN(sla_sf.id)) as sla_id'),
            ])
            ->selectSub(function ($query) use ($companyId) {
                $query->from('cuts as cu')
                    ->join('contracts as ct', 'ct.id', '=', 'cu.contract_id')
                    ->where('ct.company_id', '=', $companyId)
                    ->orderByDesc('cu.start_date')
                    ->orderByDesc('cu.id')
                    ->limit(1)
                    ->select('cu.id');
            }, 'cut_id')
            ->groupBy('sf.id', 's.id', 'ss.id')
            ->first();

        if (!$row) {
            throw new \RuntimeException('No se pudo resolver familia/servicio para el subservicio indicado en la entidad activa.');
        }

        if (empty($row->sla_id)) {
            throw new \RuntimeException('No se encontró SLA activo para la criticidad indicada.');
        }

        return [
            'family_id' => (int) $row->family_id,
            'service_id' => (int) $row->service_id,
            'sub_service_id' => (int) $row->sub_service_id,
            'sla_id' => (int) $row->sla_id,
            'cut_id' => !empty($row->cut_id) ? (int) $row->cut_id : null,
        ];
    }

    private function applySorting($query, ?string $sortBy): void
    {
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'priority_high':
                $query->orderByRaw("FIELD(criticality_level, 'CRITICA','ALTA','MEDIA','BAJA') ASC")
                    ->orderByDesc('created_at');
                break;
            case 'priority_low':
                $query->orderByRaw("FIELD(criticality_level, 'BAJA','MEDIA','ALTA','CRITICA') ASC")
                    ->orderByDesc('created_at');
                break;
            case 'status_az':
                $query->orderBy('status', 'asc')
                    ->orderByDesc('created_at');
                break;
            case 'status_za':
                $query->orderBy('status', 'desc')
                    ->orderByDesc('created_at');
                break;
            case 'recent':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

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
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('requester', function ($rq) use ($search) {
                        $rq->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('email', 'LIKE', "%{$search}%")
                           ->orWhere('department', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('subService', function ($subQ) use ($search) {
                        $subQ->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('code', 'LIKE', "%{$search}%")
                            ->orWhereHas('service', function ($serviceQ) use ($search) {
                                $serviceQ->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('code', 'LIKE', "%{$search}%")
                                    ->orWhereHas('family', function ($familyQ) use ($search) {
                                        $familyQ->where('name', 'LIKE', "%{$search}%")
                                            ->orWhere('code', 'LIKE', "%{$search}%")
                                            ->orWhereHas('contract', function ($contractQ) use ($search) {
                                                $contractQ->where('number', 'LIKE', "%{$search}%")
                                                    ->orWhere('name', 'LIKE', "%{$search}%");
                                            });
                                    });
                            });
                    });
            });
        }

        // Estado / abiertas
        if (!empty($filters['in_process'])) {
            $query->where('status', 'EN_PROCESO');
        } elseif (!empty($filters['in_course'])) {
            $query->whereNotNull('accepted_at')
                ->where('status', 'ACEPTADA');
        } elseif (!empty($filters['open'])) {
            $query->whereNotIn('status', ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA']);
        } elseif (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['exclude_closed'])) {
            $query->where('status', '!=', 'CERRADA');
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

        // Empresa
        $companyId = !empty($filters['company_id']) ? (int) $filters['company_id'] : null;
        if (!$companyId) {
            $companyId = (int) session('current_company_id');
        }
        if ($companyId > 0) {
            $query->where('company_id', $companyId);
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
                'subService.service.family:id,name,contract_id',
                'subService.service.family.contract:id,number,name,company_id',
                'sla:id,name,response_time_minutes',
                'requester:id,name,email',
                'company:id,name'
            ])
            ->select([
                'id', 'company_id', 'ticket_number', 'title', 'description', 'status',
                'criticality_level', 'requester_id', 'sub_service_id', 'sla_id',
                'created_at', 'updated_at', 'accepted_at', 'response_deadline', 'responded_at'
            ]);

        $this->applySorting($query, $filters['sort_by'] ?? 'recent');

        return $query->paginate($perPage);
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
        $technicianId = null;

        if (!empty($serviceRequest->assigned_to)) {
            $technician = Technician::withTrashed()->where('user_id', $serviceRequest->assigned_to)->first();
            if ($technician && method_exists($technician, 'trashed') && $technician->trashed()) {
                $technician->restore();
            }

            if (!$technician) {
                $technician = Technician::create([
                    'user_id' => (int) $serviceRequest->assigned_to,
                    'status' => 'active',
                    'availability_status' => 'available',
                ]);
            }

            $technicianId = $technician?->id;
        }

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
                'technician_id' => $technicianId,
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
            $selectedSubService = SubService::with(['service.family.contract', 'slas'])
                ->where('is_active', true)
                ->find($selectedSubServiceId);
        }

        $currentCompanyId = session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;

        return [
            // Se deja vacío para usar Select2 AJAX y evitar enviar listas enormes.
            'subServices' => collect(),
            'selectedSubService' => $selectedSubService,
            'requesters' => \App\Models\Requester::active()
                ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'department', 'company_id']),
            'companies' => \App\Models\Company::orderBy('name')->get(),
            'currentCompany' => $currentCompany,
            'cuts' => Cut::with('contract:id,number,company_id')
                ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                    $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                        $q->where('company_id', $currentCompanyId);
                    });
                })
                ->when($currentCompany?->active_contract_id, function ($query) use ($currentCompany) {
                    $query->where('contract_id', $currentCompany->active_contract_id);
                })
                ->orderBy('start_date', 'desc')
                ->get(['id', 'contract_id', 'name', 'start_date', 'end_date']),
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
            'subService.service.family:id,name,contract_id',
            'subService.service.family.contract:id,number,name,company_id',
            'sla:id,name,criticality_level,response_time_minutes,resolution_time_minutes',
            'requester:id,name,email,phone,position',
            'company:id,name',
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
    public function getEditFormData(?int $selectedSubServiceId = null, ?int $selectedCutId = null): array
    {
        $selectedSubService = null;
        if ($selectedSubServiceId) {
            $selectedSubService = SubService::with(['service.family.contract', 'slas'])
                ->find($selectedSubServiceId);
        }

        // Se deja vacío para usar Select2 AJAX y evitar enviar listas enormes.
        $subServices = collect();

        $currentCompanyId = session('current_company_id');
        $users = User::select(['id', 'name', 'email'])
            ->whereHas('technician', function ($query) {
                $query->where('status', 'active');
            })
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('technician.companies', function ($q) use ($currentCompanyId) {
                    $q->where('companies.id', $currentCompanyId);
                });
            })
            ->orderBy('name')
            ->get();
        $requesters = \App\Models\Requester::active()
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department', 'company_id']);
        $companies = \App\Models\Company::orderBy('name')->get();
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        $cuts = Cut::with('contract:id,number,company_id')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->when($currentCompany?->active_contract_id, function ($query) use ($currentCompany) {
                $query->where('contract_id', $currentCompany->active_contract_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'contract_id', 'name', 'start_date', 'end_date']);

        if ($selectedCutId && !$cuts->contains('id', $selectedCutId)) {
            $selectedCut = Cut::with('contract:id,number,company_id')
                ->find($selectedCutId, ['id', 'contract_id', 'name', 'start_date', 'end_date']);
            if ($selectedCut) {
                $cuts->prepend($selectedCut);
            }
        }
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
        return compact('subServices', 'selectedSubService', 'users', 'requesters', 'companies', 'cuts', 'criticalityLevels', 'currentCompany');
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
            $hasCutInPayload = array_key_exists('cut_id', $data);
            $cutIdRaw = $hasCutInPayload ? $data['cut_id'] : null;
            $cutId = ($cutIdRaw === '' || $cutIdRaw === null) ? null : (int) $cutIdRaw;
            unset($data['cut_id']);

            $previousAssignedTo = $serviceRequest->assigned_to;

            DB::transaction(function () use ($serviceRequest, $data, $hasCutInPayload, $cutId) {
                $serviceRequest->update($data);

                if ($hasCutInPayload) {
                    // Un solo corte asociado por solicitud desde este formulario.
                    $serviceRequest->cuts()->sync($cutId ? [$cutId] : []);
                }
            });

            Log::info('✅ Solicitud actualizada exitosamente', [
                'id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
            ]);

            if (array_key_exists('assigned_to', $data)) {
                $newAssignedTo = $serviceRequest->assigned_to;
                if (!empty($newAssignedTo) && $newAssignedTo !== $previousAssignedTo) {
                    $this->syncTasksTechnician($serviceRequest, (int) $newAssignedTo);
                }
            }

            return $serviceRequest;
        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncTasksTechnician(ServiceRequest $serviceRequest, int $assignedToUserId): void
    {
        $technicianId = $this->resolveTechnicianId($assignedToUserId);

        if (!$technicianId) {
            return;
        }

        Task::where('service_request_id', $serviceRequest->id)
            ->update(['technician_id' => $technicianId]);

        DB::table('schedule_blocks')
            ->join('tasks', 'tasks.id', '=', 'schedule_blocks.task_id')
            ->where('tasks.service_request_id', $serviceRequest->id)
            ->update(['schedule_blocks.technician_id' => $technicianId]);
    }

    protected function resolveTechnicianId(int $userId): ?int
    {
        $technician = Technician::withTrashed()->where('user_id', $userId)->first();
        if ($technician) {
            if (method_exists($technician, 'trashed') && $technician->trashed()) {
                $technician->restore();
            }
            $technician->status = 'active';
            $technician->availability_status = $technician->availability_status ?: 'available';
            $technician->save();
            return $technician->id;
        }

        $technician = Technician::create([
            'user_id' => $userId,
            'status' => 'active',
            'availability_status' => 'available',
        ]);

        return $technician?->id;
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
