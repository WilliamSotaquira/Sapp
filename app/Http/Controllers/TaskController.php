<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Technician;
use App\Models\ServiceRequest;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\TaskChecklist;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class TaskController extends Controller
{
    protected $assignmentService;

    public function __construct(TaskAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Listado de tareas
     */
    public function index(Request $request)
    {
        $query = Task::with(['technician.user', 'serviceRequest', 'project', 'sla']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->has('date')) {
            $query->forDate($request->date);
        }

        if ($request->has('priority') && $request->priority) {
            $priority = $request->priority;
            // Compatibilidad legacy: antes se usaba "urgent" pero en BD es "critical"
            if ($priority === 'urgent') {
                $priority = 'critical';
            }
            $query->where('priority', $priority);
        }

        $tasks = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        $technicians = Technician::with('user')
            ->active()
            ->whereHas('user')
            ->get();

        return view('tasks.index', compact('tasks', 'technicians'));
    }

    /**
     * Crear nueva tarea
     */
    public function create(Request $request)
    {
        $technicians = Technician::with('user')
            ->active()
            ->whereHas('user')
            ->get();

        // Solo solicitudes ABIERTAS (PENDIENTE, ACEPTADA, EN_PROCESO) asignadas al usuario actual
        $serviceRequests = ServiceRequest::with(['assignee.technician', 'sla'])
            ->where('assigned_to', auth()->id())
            ->whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener proyectos activos
        $projects = Project::whereIn('status', ['active', 'in_progress'])
            ->orderBy('name')
            ->get();

        $preselectedServiceRequest = null;
        $preselectedTechnicianId = null;
        $preselectedPriority = null;
        $preselectedEstimatedHours = null;

        $requestedServiceRequestId = (int) $request->query('service_request_id');

        if ($requestedServiceRequestId) {
            $preselectedServiceRequest = $serviceRequests->firstWhere('id', $requestedServiceRequestId);

            if (!$preselectedServiceRequest) {
                $preselectedServiceRequest = ServiceRequest::with(['assignee.technician', 'sla'])
                    ->where('id', $requestedServiceRequestId)
                    ->whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO'])
                    ->first();

                if ($preselectedServiceRequest && !$serviceRequests->contains('id', $preselectedServiceRequest->id)) {
                    $serviceRequests = $serviceRequests->prepend($preselectedServiceRequest);
                }
            }

            if ($preselectedServiceRequest && empty($preselectedServiceRequest->assigned_to)) {
                return redirect()
                    ->route('service-requests.show', $preselectedServiceRequest)
                    ->with('error', 'La solicitud seleccionada aún no tiene un técnico asignado. Asigna un técnico antes de crear la tarea.');
            }

            if ($preselectedServiceRequest) {
                $preselectedTechnicianId = optional(optional($preselectedServiceRequest->assignee)->technician)->id;
                $preselectedPriority = $this->mapCriticalityToTaskPriority($preselectedServiceRequest->criticality_level);

                if ($preselectedServiceRequest->sla && $preselectedServiceRequest->sla->resolution_time_minutes) {
                    $preselectedEstimatedHours = round($preselectedServiceRequest->sla->resolution_time_minutes / 60, 2);
                }
            }
        }

        $shouldSkipInitialModal = session()->has('_old_input') || ($preselectedServiceRequest && $preselectedTechnicianId);

        return view('tasks.create', [
            'technicians' => $technicians,
            'serviceRequests' => $serviceRequests,
            'projects' => $projects,
            'preselectedServiceRequest' => $preselectedServiceRequest,
            'preselectedTechnicianId' => $preselectedTechnicianId,
            'preselectedPriority' => $preselectedPriority,
            'preselectedEstimatedHours' => $preselectedEstimatedHours,
            'shouldSkipInitialModal' => $shouldSkipInitialModal,
        ]);
    }

    /**
     * Guardar nueva tarea
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:impact,regular',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'technician_id' => 'nullable|exists:technicians,id',
            'service_request_id' => 'nullable|exists:service_requests,id',
            'project_id' => 'nullable|exists:projects,id',
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => 'required|date_format:H:i',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|date_format:H:i',
            'estimated_hours' => 'nullable|numeric|min:0.1',
            // Compatibilidad legacy: aceptar "urgent" pero se normaliza a "critical" antes de guardar
            'priority' => 'required|in:urgent,high,medium,low,critical',
            'is_critical' => 'nullable|boolean',
            'requires_evidence' => 'nullable|boolean',
            'technical_complexity' => 'nullable|integer|min:1|max:5',
            'technologies' => 'nullable|string',
            'required_accesses' => 'nullable|string',
            'environment' => 'nullable|in:development,staging,production',
            'technical_notes' => 'nullable|string',
            'task_organization' => 'nullable|in:none,subtasks,checklist',
            'subtasks' => 'nullable|array',
            'subtasks.*.title' => 'required|string|max:255',
            'subtasks.*.notes' => 'nullable|string',
            'subtasks.*.estimated_minutes' => 'nullable|integer|min:5|max:480',
            'subtasks.*.priority' => 'nullable|in:urgent,high,medium,low',
            'checklist' => 'nullable|array',
            'checklist.*.item' => 'required_with:checklist|string|max:500',
            'checklist.*.order' => 'nullable|integer|min:0',
        ]);

        // Normalizaciones para mantener consistencia con enums de BD
        if (($validated['priority'] ?? null) === 'urgent') {
            $validated['priority'] = 'critical';
        }

        // Convertir checkboxes a booleanos
        $validated['is_critical'] = $request->has('is_critical');
        $validated['requires_evidence'] = $request->has('requires_evidence');

        // Valor por defecto para type
        $validated['type'] = $validated['type'] ?? 'regular';

        // Auto-marcar como crítica si tiene prioridad crítica o (alta/urgente con fecha de vencimiento)
        if ($validated['priority'] === 'critical' || 
            (in_array($validated['priority'], ['high', 'urgent']) && !empty($validated['due_date']))) {
            $validated['is_critical'] = true;
        }


        $subtasksPayload = $validated['subtasks'] ?? [];
        $checklistPayload = $validated['checklist'] ?? [];
        unset($validated['subtasks'], $validated['checklist'], $validated['task_organization']);

        $estimatedMinutes = $this->calculateEstimatedMinutes($validated, $subtasksPayload);
        $scheduledTime = $validated['scheduled_start_time'];
        $validated['estimated_duration_minutes'] = $estimatedMinutes;
        $validated['scheduled_time'] = $scheduledTime;
        $validated['estimated_hours'] = round($estimatedMinutes / 60, 2);

        $nowUi = $this->nowInUiTimezone();
        $preferredStart = $this->makeUiDateTime($validated['scheduled_date'], $validated['scheduled_start_time']);

        if ($validated['priority'] === 'urgent') {
            $preferredStart = $nowUi->copy()->addMinutes(5);
        } elseif ($preferredStart->lt($nowUi)) {
            $preferredStart = $nowUi->copy();
        }

        $technician = !empty($validated['technician_id'])
            ? Technician::find($validated['technician_id'])
            : null;

        if ($technician) {
            $slotOptions = [
                'search_days' => $validated['priority'] === 'urgent' ? 1 : 5,
                'work_start' => $validated['type'] === 'impact' ? 8 : 6,
                'work_end' => $validated['type'] === 'impact' ? 16 : 18,
                'slot_size' => 5,
            ];

            $nextSlot = $this->assignmentService->findNextAvailableSlot(
                $technician,
                $preferredStart,
                $estimatedMinutes,
                $slotOptions
            );

            if ($nextSlot) {
                $preferredStart = $nextSlot->copy();
                $validated['scheduled_date'] = $preferredStart->format('Y-m-d');
                $scheduledTime = $preferredStart->format('H:i');
                $validated['scheduled_start_time'] = $scheduledTime;
            }
        }

        $validated['estimated_duration_minutes'] = $estimatedMinutes;
        $validated['scheduled_time'] = $scheduledTime;

        // Validar que la fecha y hora no sean del pasado
        $scheduledDateTime = $this->makeUiDateTime($validated['scheduled_date'], $validated['scheduled_start_time']);
        $currentDateTime = $this->nowInUiTimezone();
        if ($scheduledDateTime->lt($currentDateTime)) {
            \Log::warning('Intento de creación de tarea con fecha pasada', [
                'scheduled' => $scheduledDateTime->toDateTimeString(),
                'current' => $currentDateTime->toDateTimeString(),
                'timezone' => $this->getUiTimezone(),
                'input_date' => $validated['scheduled_date'],
                'input_time' => $validated['scheduled_start_time'],
            ]);
            return back()->withErrors([
                'scheduled_date' => 'No se puede asignar una tarea en una fecha y hora pasadas.'
            ])->withInput();
        }

        // Validar horario laboral (6:00 - 18:00)
        $hour = (int) explode(':', $validated['scheduled_start_time'])[0];
        if ($hour < 6 || $hour >= 18) {
            return back()->withErrors([
                'scheduled_start_time' => 'La hora debe estar dentro del horario laboral (6:00 - 18:00).'
            ])->withInput();
        }

        // Si hay técnico asignado, marcaremos para enviar a agenda
        $autoAssignToCalendar = !empty($validated['technician_id']);
        if ($autoAssignToCalendar && !empty($validated['service_request_id'])) {
            // La tarea se confirmará automáticamente cuando viene de una solicitud
            $validated['status'] = 'confirmed';
        }

        // Asegurar que los campos opcionales tengan valores predeterminados
        $validated['technical_complexity'] = $validated['technical_complexity'] ?? null;
        $validated['environment'] = $validated['environment'] ?? null;
        $validated['technical_notes'] = $validated['technical_notes'] ?? null;

        // Crear tarea con generación atómica de código
        \Log::info("=== INICIO CREACIÓN DE TAREA ===");

        $date = $this->makeUiDate($validated['scheduled_date']);
        $prefix = $validated['type'] === 'impact' ? 'IMP' : 'REG';
        $dateStr = $date->format('Ymd');
        $lockName = "task_code_gen_{$prefix}_{$dateStr}";

        \Log::info("Lock name: " . $lockName);

        // Obtener lock de aplicación (espera hasta 10 segundos)
        $lockAcquired = \DB::selectOne("SELECT GET_LOCK(?, 10) as result", [$lockName])->result;

        \Log::info("Lock acquired: " . ($lockAcquired ? 'YES' : 'NO'));

        if (!$lockAcquired) {
            return back()->withErrors(['task_code' => 'No se pudo generar el código de tarea. Intente nuevamente.'])->withInput();
        }

        $task = null;
        try {
            // Realizar todo dentro de UNA transacción
            \DB::beginTransaction();

            // Obtener último código con lock de fila (incluir borrados)
            $lastTask = Task::withTrashed()
                ->where('task_code', 'like', "{$prefix}-{$dateStr}-%")
                ->lockForUpdate()
                ->orderBy('task_code', 'desc')
                ->first();

            $sequence = 1;
            if ($lastTask) {
                $parts = explode('-', $lastTask->task_code);
                if (isset($parts[2])) {
                    $lastSequence = intval($parts[2]);
                    $sequence = $lastSequence + 1;

                    // Log para debug
                    \Log::info("Generando código de tarea", [
                        'last_code' => $lastTask->task_code,
                        'last_sequence' => $lastSequence,
                        'new_sequence' => $sequence
                    ]);
                }
            }

            // Asignar el código
            $taskCode = sprintf('%s-%s-%03d', $prefix, $dateStr, $sequence);
            $validated['task_code'] = $taskCode;
            $validated['created_at'] = now();
            $validated['updated_at'] = now();

            \Log::info("DEBUG: Intentando insertar tarea", [
                'last_task_code' => $lastTask ? $lastTask->task_code : 'NINGUNA',
                'sequence_calculated' => $sequence,
                'new_code' => $taskCode,
                'validated_code' => $validated['task_code']
            ]);

            $taskData = Arr::except($validated, ['subtasks', 'checklist', 'task_organization']);

            \Log::debug('Insert task payload keys', [
                'keys' => array_keys($taskData)
            ]);

            // Insertar directamente
            \DB::table('tasks')->insert($taskData);

            // Commit de la transacción
            \DB::commit();

            \Log::info("Tarea insertada exitosamente: " . $validated['task_code']);

            // Buscar la tarea creada
            $task = Task::where('task_code', $validated['task_code'])->first();

        } catch (\Exception $e) {
            \Log::error("Error al insertar tarea", [
                'code_attempted' => $validated['task_code'] ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            \DB::rollBack();
            \DB::selectOne("SELECT RELEASE_LOCK(?)", [$lockName]);
            throw $e;
        }

        // Liberar lock DESPUÉS de todo
        \DB::selectOne("SELECT RELEASE_LOCK(?)", [$lockName]);

        if ($autoAssignToCalendar && $task) {
            $this->assignmentService->createScheduleBlock($task);
        }

        // Crear subtareas si se seleccionó
        if (!empty($subtasksPayload) && is_array($subtasksPayload)) {
            foreach ($subtasksPayload as $subtaskData) {
                $task->subtasks()->create(array_merge($subtaskData, [
                    'technician_id' => $validated['technician_id'],
                    'status' => 'pending',
                ]));
            }
        }

        // Crear checklist si se seleccionó
        if (!empty($checklistPayload) && is_array($checklistPayload)) {
            foreach ($checklistPayload as $index => $checklistItem) {
                // Filtrar items vacíos
                if (empty($checklistItem['item'])) {
                    continue;
                }
                $task->checklists()->create([
                    'title' => $checklistItem['item'],
                    'order' => $checklistItem['order'] ?? $index,
                    'is_completed' => false,
                ]);
            }
        }

        // Detectar horarios no hábiles
        $nonWorkingWarnings = [];
        $scheduledDate = $this->makeUiDate($validated['scheduled_date']);

        if ($scheduledDate->dayOfWeek === 0) {
            $nonWorkingWarnings[] = 'Domingo';
        }

        if ($hour < 8) {
            $nonWorkingWarnings[] = 'Antes de las 8:00 AM';
        } elseif ($hour >= 16) {
            $nonWorkingWarnings[] = 'Después de las 4:00 PM';
        }

        // Registrar en el historial
        $notes = 'Tarea creada';
        if ($autoAssignToCalendar && !empty($validated['service_request_id'])) {
            $notes .= ' y asignada automáticamente a la agenda del técnico desde solicitud de servicio';
        } elseif ($autoAssignToCalendar) {
            $notes .= ' y agendada automáticamente en la agenda del técnico';
        }
        if (!empty($nonWorkingWarnings)) {
            $notes .= ' (HORARIO NO HÁBIL: ' . implode(', ', $nonWorkingWarnings) . ')';
        }

        \App\Models\TaskHistory::create([
            'task_id' => $task->id,
            'action' => 'created',
            'user_id' => auth()->id(),
            'notes' => $notes,
            'metadata' => [
                'type' => $validated['type'],
                'priority' => $validated['priority'],
                'non_working_warnings' => $nonWorkingWarnings,
                'auto_assigned_to_calendar' => $autoAssignToCalendar
            ]
        ]);

        // Si hay técnico asignado, registrar asignación
        if (!empty($validated['technician_id'])) {
            \App\Models\TaskHistory::create([
                'task_id' => $task->id,
                'action' => 'assigned',
                'user_id' => auth()->id(),
                'notes' => 'Tarea asignada al técnico',
                'metadata' => ['technician_id' => $validated['technician_id']]
            ]);
        }

        // Si hay service request, asociar SLA
        if ($task->service_request_id) {
            $serviceRequest = $task->serviceRequest;
            $task->update(['sla_id' => $serviceRequest->sla_id]);

            // Crear registro de cumplimiento SLA
            $this->createSlaCompliance($task);
        }

        $successMessage = 'Tarea creada exitosamente';
        if ($autoAssignToCalendar && !empty($validated['service_request_id'])) {
            $successMessage .= ' y confirmada en la agenda del técnico.';
        } elseif ($autoAssignToCalendar) {
            $successMessage .= ' y agendada en la agenda del técnico.';
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', $successMessage);
    }

    public function quickStoreForServiceRequest(Request $request, ServiceRequest $serviceRequest)
    {
        if (!auth()->user()->can('assign-service-requests')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear tareas.',
            ], 403);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                // Compatibilidad legacy: aceptar "urgent" pero se normaliza a "critical" antes de guardar
                'priority' => 'required|in:low,medium,high,critical,urgent',
                'duration_minutes' => 'nullable|integer|min:5|max:480',
                'type' => 'nullable|in:impact,regular',
            ],
            [
                'title.required' => 'El título es obligatorio.',
                'title.max' => 'El título no puede superar los 255 caracteres.',
                'description.string' => 'La descripción debe ser un texto válido.',
                'priority.required' => 'Selecciona la prioridad de la tarea.',
                'priority.in' => 'La prioridad seleccionada no es válida.',
                'duration_minutes.integer' => 'La duración debe ser un número entero.',
                'duration_minutes.min' => 'La duración mínima es de 5 minutos.',
                'duration_minutes.max' => 'La duración no puede exceder 480 minutos.',
                'type.in' => 'El tipo de tarea seleccionado no es válido.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (($validated['priority'] ?? null) === 'urgent') {
            $validated['priority'] = 'critical';
        }

        $technician = $serviceRequest->assigned_to
            ? Technician::where('user_id', $serviceRequest->assigned_to)->first()
            : null;

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud no tiene técnico asignado. Asigna un técnico antes de crear tareas.',
            ], 422);
        }

        $date = now()->hour < 18 ? now() : now()->addDay();
        $durationMinutes = $validated['duration_minutes'] ?? 60;
        $estimatedHours = round($durationMinutes / 60, 2);

        $task = Task::create([
            'type' => $validated['type'] ?? 'regular',
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'technician_id' => $technician->id,
            'service_request_id' => $serviceRequest->id,
            'sla_id' => $serviceRequest->sla_id,
            'scheduled_date' => $date->format('Y-m-d'),
            'scheduled_start_time' => $date->format('H:i'),
            'scheduled_time' => $date->format('H:i'),
            'estimated_duration_minutes' => $durationMinutes,
            'estimated_hours' => $estimatedHours,
            'priority' => $validated['priority'],
            'status' => 'pending',
        ]);

        $task->addHistory('created', auth()->id(), 'Tarea creada rápidamente desde la solicitud.');

        if ($task->technician_id) {
            $task->addHistory('assigned', auth()->id(), "Asignada a {$task->technician->user->name}");
        }

        if ($task->service_request_id && $serviceRequest->sla_id) {
            $this->createSlaCompliance($task);
        }

        $task->load(['technician.user', 'subtasks']);

        return response()->json([
            'success' => true,
            'message' => 'Tarea creada correctamente.',
            'html' => view('components.service-requests.show.content.partials.task-card', compact('task'))->render(),
        ]);
    }

    /**
     * Ver tarea
     */
    public function show(Task $task)
    {
        $task->load([
            'technician.user',
            'serviceRequest',
            'project',
            'sla',
            'history.user',
            'dependencies.dependsOnTask',
            'dependents.task',
            'slaCompliance',
            'gitAssociations',
            'knowledgeBase',
            'subtasks',
            'checklists'
        ]);

        return view('tasks.show', compact('task'));
    }

    /**
     * Editar tarea
     */
    public function edit(Task $task)
    {
        $technicians = Technician::with('user')
            ->active()
            ->whereHas('user')
            ->get();

        // Solo solicitudes ABIERTAS (PENDIENTE, ACEPTADA, EN_PROCESO) asignadas al usuario actual
        $serviceRequests = ServiceRequest::where('assigned_to', auth()->id())
            ->whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Si la tarea tiene una solicitud asociada que no está en la lista, agregarla
        if ($task->service_request_id && !$serviceRequests->contains('id', $task->service_request_id)) {
            $currentServiceRequest = ServiceRequest::find($task->service_request_id);
            if ($currentServiceRequest) {
                $serviceRequests->prepend($currentServiceRequest);
            }
        }

        $projects = Project::whereIn('status', ['active', 'in_progress'])
            ->orderBy('name')
            ->get();

        return view('tasks.edit', compact('task', 'technicians', 'serviceRequests', 'projects'));
    }

    /**
     * Actualizar tarea
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:impact,regular',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'technician_id' => 'nullable|exists:technicians,id',
            'service_request_id' => 'nullable|exists:service_requests,id',
            'project_id' => 'nullable|exists:projects,id',
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => 'required|date_format:H:i',
            'estimated_hours' => 'required|numeric|min:0.1',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|date_format:H:i',
            // Compatibilidad legacy: aceptar "urgent" pero se normaliza a "critical" antes de guardar
            'priority' => 'required|in:critical,high,medium,low,urgent',
            'is_critical' => 'nullable|boolean',
            'requires_evidence' => 'nullable|boolean',
            // Compatibilidad legacy: aceptar "confirmed" pero se normaliza a "pending" antes de guardar
            'status' => 'required|in:pending,confirmed,in_progress,blocked,in_review,completed,cancelled,rescheduled',
            'technical_complexity' => 'nullable|integer|min:1|max:5',
            'technologies' => 'nullable|string',
            'required_accesses' => 'nullable|string',
            'environment' => 'nullable|in:development,staging,production',
            'technical_notes' => 'nullable|string',
        ]);

        // Normalizaciones para mantener consistencia con enums de BD
        if (($validated['priority'] ?? null) === 'urgent') {
            $validated['priority'] = 'critical';
        }
        if (($validated['status'] ?? null) === 'confirmed') {
            $validated['status'] = 'pending';
        }

        // Convertir strings vacíos a null
        if (empty($validated['service_request_id'])) {
            $validated['service_request_id'] = null;
        }
        if (empty($validated['project_id'])) {
            $validated['project_id'] = null;
        }
        if (empty($validated['due_date'])) {
            $validated['due_date'] = null;
        }
        if (empty($validated['due_time'])) {
            $validated['due_time'] = null;
        }

        // Asignar valor por defecto para type si no se proporciona
        if (empty($validated['type'])) {
            $validated['type'] = 'regular';
        }

        // Convertir checkboxes a booleanos
        $validated['is_critical'] = $request->has('is_critical') && $request->input('is_critical') == '1';
        $validated['requires_evidence'] = $request->has('requires_evidence') && $request->input('requires_evidence') == '1';

        // Auto-marcar como crítica si la prioridad es "critical" o si es "high"/"urgent" con fecha de vencimiento
        if ($validated['priority'] === 'critical' || 
            (in_array($validated['priority'], ['high', 'urgent']) && !empty($validated['due_date']))) {
            $validated['is_critical'] = true;
        }

        // Validar que la fecha y hora no sean del pasado
        $scheduledDateTime = $this->makeUiDateTime($validated['scheduled_date'], $validated['scheduled_start_time']);
        $currentDateTime = $this->nowInUiTimezone();
        if ($scheduledDateTime->lt($currentDateTime)) {
            return back()->withErrors([
                'scheduled_date' => 'No se puede asignar una tarea en una fecha y hora pasadas.'
            ])->withInput();
        }

        // Validar horario laboral (6:00 - 18:00)
        $hour = (int) explode(':', $validated['scheduled_start_time'])[0];
        if ($hour < 6 || $hour >= 18) {
            return back()->withErrors([
                'scheduled_start_time' => 'La hora debe estar dentro del horario laboral (6:00 - 18:00).'
            ])->withInput();
        }

        // Registrar cambios significativos en el historial
        $changes = [];

        if ($task->status !== $validated['status']) {
            $changes[] = "Estado cambió de {$task->status} a {$validated['status']}";
        }

        if ($task->priority !== $validated['priority']) {
            $changes[] = "Prioridad cambió de {$task->priority} a {$validated['priority']}";
        }

        if ($task->is_critical != $validated['is_critical']) {
            $changes[] = $validated['is_critical'] ? 'Marcada como crítica' : 'Desmarcada como crítica';
        }

        // Detectar horarios no hábiles
        $nonWorkingWarnings = [];
        $scheduledDate = $this->makeUiDate($validated['scheduled_date']);

        if ($scheduledDate->dayOfWeek === 0) {
            $nonWorkingWarnings[] = 'Domingo';
        }

        if ($hour < 8) {
            $nonWorkingWarnings[] = 'Antes de las 8:00 AM';
        } elseif ($hour >= 16) {
            $nonWorkingWarnings[] = 'Después de las 4:00 PM';
        }

        if (!empty($nonWorkingWarnings)) {
            $changes[] = "ADVERTENCIA: Horario no hábil (" . implode(', ', $nonWorkingWarnings) . ")";
        }

        // Log temporal para depuración
        \Log::info('Actualizando tarea', [
            'task_id' => $task->id,
            'validated_data' => $validated,
            'changes' => $changes
        ]);

        $task->update($validated);

        // Registrar en historial si hubo cambios significativos
        if (!empty($changes)) {
            $task->addHistory('updated', auth()->id(), implode('. ', $changes));
        }

        $successMessage = 'Tarea actualizada exitosamente';
        if (!empty($changes)) {
            $successMessage .= ': ' . implode(', ', $changes);
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', $successMessage);
    }

    /**
     * Asignar tarea a técnico
     */
    public function assign(Request $request, Task $task)
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:technicians,id',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|date_format:H:i',
        ]);

        $technician = Technician::find($validated['technician_id']);

        // Verificar capacidad
        $date = $validated['scheduled_date'] ?? $task->scheduled_date;
        $time = $validated['scheduled_time'] ?? $task->scheduled_time;

        // Validar que la fecha y hora no sean del pasado
        if ($date && $time) {
            $scheduledDateTime = $this->makeUiDateTime($date, $time);
            if ($scheduledDateTime->lt($this->nowInUiTimezone())) {
                return back()->withErrors([
                    'scheduled_date' => 'No se puede asignar una tarea en una fecha y hora pasadas.'
                ])->withInput();
            }
        }

        if (!$this->assignmentService->canAssignTask($technician, $task, $date, $time)) {
            return back()->with('error', 'El técnico no tiene capacidad disponible en ese horario');
        }

        // Asignar tarea
        $task->update([
            'technician_id' => $technician->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'status' => 'pending',
        ]);

        $task->addHistory('assigned', auth()->id(), "Asignado a {$technician->user->name}");

        // Crear bloque de horario
        $this->assignmentService->createScheduleBlock($task);

        return back()->with('success', 'Tarea asignada exitosamente');
    }

    /**
     * Sugerir asignación automática
     */
    public function suggestAssignment(Task $task)
    {
        $suggestions = $this->assignmentService->suggestTechnicianForTask($task);

        return response()->json($suggestions);
    }

    /**
     * Iniciar tarea
     */
    public function start(Task $task)
    {
        if (!in_array($task->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Solo se pueden iniciar tareas pendientes o confirmadas');
        }

        $task->start();

        return back()->with('success', 'Tarea iniciada');
    }

    /**
     * Completar tarea
     */
    public function complete(Request $request, Task $task)
    {
        $validated = $request->validate([
            'technical_notes' => 'nullable|string',
            'actual_duration_minutes' => 'nullable|integer',
        ]);

        if ($task->status !== 'in_progress') {
            return back()->with('error', 'Solo se pueden completar tareas en progreso');
        }

        $task->complete($validated['technical_notes'] ?? null);

        $task->subtasks()->update([
            'status' => 'completed',
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        if (isset($validated['actual_duration_minutes'])) {
            $task->update(['actual_duration_minutes' => $validated['actual_duration_minutes']]);
        }

        // Actualizar cumplimiento SLA
        if ($task->slaCompliance) {
            $task->slaCompliance->calculateCompliance();
        }

        return back()->with('success', 'Tarea completada exitosamente');
    }

    /**
     * Bloquear tarea
     */
    public function block(Request $request, Task $task)
    {
        $validated = $request->validate([
            'block_reason' => 'required|string',
        ]);

        $task->block($validated['block_reason']);

        return back()->with('warning', 'Tarea bloqueada');
    }

    /**
     * Desbloquear tarea
     */
    public function unblock(Task $task)
    {
        $task->unblock();

        return back()->with('success', 'Tarea desbloqueada');
    }

    /**
     * Reprogramar tarea
     */
    public function reschedule(Request $request, Task $task)
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => ['required', 'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'reason' => 'nullable|string',
        ]);

        // Validar que la fecha y hora no sean del pasado
        $scheduledDateTime = $this->makeUiDateTime($validated['scheduled_date'], $validated['scheduled_start_time']);
        $currentDateTime = $this->nowInUiTimezone();
        if ($scheduledDateTime->lt($currentDateTime)) {
            // Si es AJAX, devolver error JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede asignar una tarea en una fecha y hora pasadas.'
                ], 422);
            }

            return back()->withErrors([
                'scheduled_date' => 'No se puede asignar una tarea en una fecha y hora pasadas.'
            ])->withInput();
        }

        // Validar horario laboral (6:00 - 18:00)
        $hour = (int) explode(':', $validated['scheduled_start_time'])[0];
        if ($hour < 6 || $hour >= 18) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La hora debe estar dentro del horario laboral (6:00 - 18:00).'
                ], 422);
            }

            return back()->withErrors([
                'scheduled_start_time' => 'La hora debe estar dentro del horario laboral (6:00 - 18:00).'
            ])->withInput();
        }

        $oldDate = $task->scheduled_date ? $task->scheduled_date->format('Y-m-d') : 'sin fecha';
        $oldTime = $task->scheduled_start_time ?? 'sin hora';

        $task->update([
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_start_time' => $validated['scheduled_start_time'],
        ]);

        // Detectar horarios no hábiles
        $nonWorkingWarnings = [];
        $scheduledDate = $this->makeUiDate($validated['scheduled_date']);

        if ($scheduledDate->dayOfWeek === 0) {
            $nonWorkingWarnings[] = 'Domingo';
        }

        if ($hour < 8) {
            $nonWorkingWarnings[] = 'Antes de las 8:00 AM';
        } elseif ($hour >= 16) {
            $nonWorkingWarnings[] = 'Después de las 4:00 PM';
        }

        $reason = $validated['reason'] ?? "De {$oldDate} {$oldTime} a {$validated['scheduled_date']} {$validated['scheduled_start_time']}";

        if (!empty($nonWorkingWarnings)) {
            $reason .= " (HORARIO NO HÁBIL: " . implode(', ', $nonWorkingWarnings) . ")";
        }

        $task->addHistory('rescheduled', auth()->id(), $reason);

        // Actualizar bloque de horario si existe
        if ($task->scheduleBlock) {
            $task->scheduleBlock->update([
                'block_date' => $validated['scheduled_date'],
                'start_time' => $validated['scheduled_start_time'],
            ]);
        }

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tarea reprogramada exitosamente',
                'task' => $task->fresh()
            ]);
        }

        return back()->with('success', 'Tarea reprogramada');
    }

    /**
     * Actualizar duración de tarea
     */
    public function updateDuration(Request $request, Task $task)
    {
        $validated = $request->validate([
            'estimated_hours' => 'required|numeric|min:0.25|max:24',
        ]);

        $oldDuration = $task->estimated_hours;

        $task->update([
            'estimated_hours' => $validated['estimated_hours']
        ]);

        $hours = floor($validated['estimated_hours']);
        $mins = round(($validated['estimated_hours'] - $hours) * 60);
        $newDurationText = $hours > 0 ? "{$hours}h {$mins}min" : "{$mins}min";

        $task->addHistory('updated', auth()->id(), "Duración actualizada de {$oldDuration}h a {$newDurationText}");

        // Si es petición AJAX, devolver JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Duración actualizada exitosamente',
                'task' => $task->fresh()
            ]);
        }

        return back()->with('success', 'Duración de tarea actualizada');
    }

    /**
     * Eliminar tarea
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Tarea eliminada');
    }

    /**
     * Crear registro de cumplimiento SLA
     */
    protected function createSlaCompliance(Task $task)
    {
        if (!$task->sla) {
            return;
        }

        $sla = $task->sla;

        $task->slaCompliance()->create([
            'service_request_id' => $task->service_request_id,
            'sla_id' => $sla->id,
            'sla_response_time_minutes' => $sla->response_time_minutes,
            'sla_resolution_time_minutes' => $sla->resolution_time_minutes,
            'sla_deadline' => now()->addMinutes($sla->resolution_time_minutes),
            'compliance_status' => 'within_sla',
        ]);
    }

    protected function calculateEstimatedMinutes(array $data, array $subtasks = []): int
    {
        $subtaskMinutes = 0;
        foreach ($subtasks as $subtask) {
            if (!empty($subtask['estimated_minutes']) && is_numeric($subtask['estimated_minutes'])) {
                $subtaskMinutes += max(5, (int) $subtask['estimated_minutes']);
            }
        }

        if ($subtaskMinutes > 0) {
            return $subtaskMinutes;
        }

        if (!empty($data['estimated_hours'])) {
            return max(15, (int) round((float) $data['estimated_hours'] * 60));
        }

        return ($data['type'] ?? 'regular') === 'impact' ? 90 : 25;
    }

    /**
     * Mapear la criticidad de la solicitud al tipo de prioridad de tareas
     */
    protected function mapCriticalityToTaskPriority(?string $criticality): ?string
    {
        if (empty($criticality)) {
            return null;
        }

        $map = [
            'BAJA' => 'low',
            'MEDIA' => 'medium',
            'ALTA' => 'high',
            'URGENTE' => 'urgent',
            'CRITICA' => 'urgent',
            'LOW' => 'low',
            'MEDIUM' => 'medium',
            'HIGH' => 'high',
            'CRITICAL' => 'urgent',
        ];

        $key = strtoupper($criticality);

        return $map[$key] ?? null;
    }

    protected function getUiTimezone(): string
    {
        $timezone = config('app.ui_timezone');

        if (empty($timezone) || $timezone === 'UTC') {
            $envTimezone = env('APP_UI_TIMEZONE', env('APP_TIMEZONE'));
            if (!empty($envTimezone)) {
                $timezone = $envTimezone;
            }
        }

        if (empty($timezone) || $timezone === 'UTC') {
            $timezone = 'America/Bogota';
        }

        return $timezone;
    }

    protected function nowInUiTimezone(): Carbon
    {
        return Carbon::now($this->getUiTimezone());
    }

    protected function makeUiDateTime(string $date, string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", $this->getUiTimezone());
    }

    protected function makeUiDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date, $this->getUiTimezone());
    }

    // =============================================================================
    // GESTIÓN DE SUBTAREAS
    // =============================================================================

    public function storeSubtask(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:high,medium,low',
            'estimated_minutes' => 'nullable|integer|min:5|max:480',
        ]);

        $validated['order'] = $task->subtasks()->max('order') + 1;
        $subtask = $task->subtasks()->create($validated);

        if ($task->status === 'completed') {
            $task->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'subtask' => $subtask,
                'task_status' => $task->status,
            ]);
        }

        return back()->with('success', 'Subtarea creada');
    }

    public function updateSubtask(Request $request, Task $task, Subtask $subtask)
    {
        if ($subtask->task_id !== $task->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:high,medium,low',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $subtask->update($validated);

        return back()->with('success', 'Subtarea actualizada');
    }

    public function destroySubtask(Task $task, Subtask $subtask)
    {
        if ($subtask->task_id !== $task->id) {
            abort(403);
        }

        $subtask->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Subtarea eliminada']);
        }

        return back()->with('success', 'Subtarea eliminada');
    }

    public function toggleSubtaskStatus(Task $task, Subtask $subtask)
    {
        if ($subtask->task_id !== $task->id) {
            abort(403);
        }

        if ($subtask->status === 'completed') {
            $subtask->update(['status' => 'pending', 'completed_at' => null]);
        } else {
            $subtask->complete();
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_completed' => $subtask->status === 'completed',
                'message' => 'Subtarea actualizada'
            ]);
        }

        return back();
    }

    // =============================================================================
    // GESTIÓN DE CHECKLISTS
    // =============================================================================

    public function storeChecklist(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $validated['order'] = $task->checklists()->max('order') + 1;
        $task->checklists()->create($validated);

        return back()->with('success', 'Item creado');
    }

    public function updateChecklist(Request $request, Task $task, TaskChecklist $checklist)
    {
        if ($checklist->task_id !== $task->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist->update($validated);

        return back()->with('success', 'Item actualizado');
    }

    public function destroyChecklist(Task $task, TaskChecklist $checklist)
    {
        if ($checklist->task_id !== $task->id) {
            abort(403);
        }

        $checklist->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Item eliminado']);
        }

        return back()->with('success', 'Item eliminado');
    }

    public function toggleChecklist(Task $task, TaskChecklist $checklist)
    {
        if ($checklist->task_id !== $task->id) {
            abort(403);
        }

        $checklist->toggle();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_completed' => $checklist->is_completed,
                'message' => 'Checklist actualizado'
            ]);
        }

        return back();
    }

    /**
     * Toggle task completion status
     */
    public function toggleStatus(Task $task, Request $request)
    {
        try {
            $completed = $request->input('completed', false);

            if ($completed) {
                // Marcar como completada
                $task->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Marcar todas las subtareas como completadas
                $task->subtasks()->update([
                    'is_completed' => true,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $message = 'Tarea marcada como completada';
            } else {
                // Volver a en proceso
                $task->update([
                    'status' => 'in_progress',
                    'completed_at' => null,
                ]);

                // Desmarcar todas las subtareas
                $task->subtasks()->update([
                    'is_completed' => false,
                    'status' => 'pending',
                    'completed_at' => null,
                ]);

                $message = 'Tarea marcada como en proceso';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $task->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle subtask completion status
     */
    public function toggleSubtask(Task $task, Subtask $subtask, Request $request)
    {
        try {
            if ($subtask->task_id !== $task->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La subtarea no pertenece a esta tarea'
                ], 403);
            }

            $previousStatus = $task->status;
            $isCompleted = filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);

            $subtask->update([
                'is_completed' => $isCompleted,
                'status' => $isCompleted ? 'completed' : 'pending',
                'completed_at' => $isCompleted ? now() : null,
            ]);

            if (!$isCompleted && $task->status === 'completed') {
                $task->update([
                    'status' => 'in_progress',
                    'completed_at' => null,
                ]);
            }

            $totalSubtasks = $task->subtasks()->count();
            $pendingSubtasks = $task->subtasks()->where('is_completed', false)->count();

            if ($totalSubtasks > 0 && $pendingSubtasks === 0 && $task->status !== 'completed') {
                $task->refresh();
                if ($task->status !== 'completed') {
                    $task->complete();
                }
            }

            if ($pendingSubtasks > 0 && $task->status === 'completed') {
                $task->update([
                    'status' => 'in_progress',
                    'completed_at' => null,
                ]);
            }

            $task->refresh();

            $message = $isCompleted
                ? 'Subtarea marcada como completada'
                : 'Subtarea marcada como pendiente';

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_completed' => $subtask->is_completed,
                'task_status' => $task->status,
                'status_changed' => $previousStatus !== $task->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la subtarea: ' . $e->getMessage()
            ], 500);
        }
    }
}
