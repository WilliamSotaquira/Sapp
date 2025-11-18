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
use Carbon\Carbon;

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

        $tasks = $query->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
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
    public function create()
    {
        $technicians = Technician::with('user')
            ->active()
            ->whereHas('user')
            ->get();

        // Obtener solicitudes de servicio ACEPTADAS y asignadas al usuario actual
        $serviceRequests = ServiceRequest::where('assigned_to', auth()->id())
            ->where('status', 'ACEPTADA')
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener proyectos activos
        $projects = Project::whereIn('status', ['active', 'in_progress'])
            ->orderBy('name')
            ->get();

        return view('tasks.create', compact('technicians', 'serviceRequests', 'projects'));
    }

    /**
     * Guardar nueva tarea
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:impact,regular',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'technician_id' => 'nullable|exists:technicians,id',
            'service_request_id' => 'nullable|exists:service_requests,id',
            'project_id' => 'nullable|exists:projects,id',
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => 'required|date_format:H:i',
            'estimated_hours' => 'required|numeric|min:0.1',
            'priority' => 'required|in:critical,high,medium,low',
            'technical_complexity' => 'nullable|integer|min:1|max:5',
            'technologies' => 'nullable|string',
            'required_accesses' => 'nullable|string',
            'environment' => 'nullable|in:development,staging,production',
            'technical_notes' => 'nullable|string',
        ]);

        // Validar que la fecha y hora no sean del pasado
        $scheduledDateTime = \Carbon\Carbon::parse($validated['scheduled_date'] . ' ' . $validated['scheduled_start_time']);
        if ($scheduledDateTime->isPast()) {
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

        // Asegurar valores por defecto para campos que no pueden ser null
        $validated['technical_complexity'] = $validated['technical_complexity'] ?? 3;
        $validated['environment'] = $validated['environment'] ?? 'production';

        $task = Task::create($validated);

        // Detectar horarios no hábiles
        $nonWorkingWarnings = [];
        $scheduledDate = \Carbon\Carbon::parse($validated['scheduled_date']);

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
                'non_working_warnings' => $nonWorkingWarnings
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

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Tarea creada exitosamente');
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

        // Obtener solicitudes de servicio ACEPTADAS y asignadas al usuario actual
        $serviceRequests = ServiceRequest::where('assigned_to', auth()->id())
            ->where('status', 'ACEPTADA')
            ->orderBy('created_at', 'desc')
            ->get();

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
            'type' => 'required|in:impact,regular',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'technician_id' => 'nullable|exists:technicians,id',
            'service_request_id' => 'nullable|exists:service_requests,id',
            'project_id' => 'nullable|exists:projects,id',
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => 'required|date_format:H:i',
            'estimated_hours' => 'required|numeric|min:0.1',
            'priority' => 'required|in:critical,high,medium,low',
            'status' => 'required|in:pending,in_progress,blocked,in_review,completed,cancelled,rescheduled',
            'technical_complexity' => 'nullable|integer|min:1|max:5',
            'technologies' => 'nullable|string',
            'required_accesses' => 'nullable|string',
            'environment' => 'nullable|in:development,staging,production',
            'technical_notes' => 'nullable|string',
        ]);

        // Validar que la fecha y hora no sean del pasado
        $scheduledDateTime = \Carbon\Carbon::parse($validated['scheduled_date'] . ' ' . $validated['scheduled_start_time']);
        if ($scheduledDateTime->isPast()) {
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

        // Asegurar valores por defecto para campos que no pueden ser null
        $validated['technical_complexity'] = $validated['technical_complexity'] ?? 3;
        $validated['environment'] = $validated['environment'] ?? 'production';

        // Registrar cambios significativos en el historial
        $changes = [];

        if ($task->status !== $validated['status']) {
            $changes[] = "Estado cambió de {$task->status} a {$validated['status']}";
        }

        if ($task->priority !== $validated['priority']) {
            $changes[] = "Prioridad cambió de {$task->priority} a {$validated['priority']}";
        }

        // Detectar horarios no hábiles
        $nonWorkingWarnings = [];
        $scheduledDate = \Carbon\Carbon::parse($validated['scheduled_date']);

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

        $task->update($validated);

        // Registrar en historial si hubo cambios significativos
        if (!empty($changes)) {
            $task->addHistory('updated', auth()->id(), implode('. ', $changes));
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Tarea actualizada exitosamente');
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
            $scheduledDateTime = \Carbon\Carbon::parse($date . ' ' . $time);
            if ($scheduledDateTime->lt(now()->subMinutes(5))) {
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
        if ($task->status !== 'pending') {
            return back()->with('error', 'Solo se pueden iniciar tareas pendientes');
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
        $scheduledDateTime = \Carbon\Carbon::parse($validated['scheduled_date'] . ' ' . $validated['scheduled_start_time']);
        if ($scheduledDateTime->isPast()) {
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
        $scheduledDate = \Carbon\Carbon::parse($validated['scheduled_date']);

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

    // =============================================================================
    // GESTIÓN DE SUBTAREAS
    // =============================================================================

    public function storeSubtask(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:high,medium,low',
        ]);

        $validated['order'] = $task->subtasks()->max('order') + 1;
        $task->subtasks()->create($validated);

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

        return back()->with('success', 'Item eliminado');
    }

    public function toggleChecklist(Task $task, TaskChecklist $checklist)
    {
        if ($checklist->task_id !== $task->id) {
            abort(403);
        }

        $checklist->toggle();

        return back();
    }
}
