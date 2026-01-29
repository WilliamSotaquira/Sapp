<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\Task;
use App\Models\ScheduleBlock;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TechnicianScheduleController extends Controller
{
    /**
     * Vista principal del calendario de técnicos
     */
    public function index(Request $request)
    {
        // Detectar si es móvil basado en el ancho de pantalla o user agent
        $isMobile = $request->header('sec-ch-ua-mobile') === '?1' ||
                    preg_match('/(android|iphone|ipad|mobile)/i', $request->userAgent());

        // Vista predeterminada: 'day' para móvil, 'week' para desktop
        $defaultView = $isMobile ? 'day' : 'week';
        $view = $request->get('view', $defaultView); // day, week, month
        $date = $request->get('date', now()->format('Y-m-d'));
        $technicianId = $request->get('technician_id');

        $technicians = Technician::with('user')->active()->get();

        $data = match($view) {
            'day' => $this->getDayView($date, $technicianId),
            'week' => $this->getWeekView($date, $technicianId),
            'month' => $this->getMonthView($date, $technicianId),
            default => $this->getWeekView($date, $technicianId),
        };

        return view('technician-schedule.index', array_merge(
            $data,
            compact('view', 'date', 'technicians', 'technicianId')
        ));
    }

    /**
     * Vista de día
     */
    protected function getDayView($date, $technicianId = null)
    {
        $query = Task::with(['technician.user', 'serviceRequest', 'sla'])
            ->forDate($date);

        if ($technicianId) {
            $query->forTechnician($technicianId);
        }

        $tasks = $query->orderByRaw("CASE WHEN scheduled_order IS NULL OR scheduled_order = 0 THEN 1 ELSE 0 END")
            ->orderBy('scheduled_order')
            ->orderBy('scheduled_start_time')
            ->get();

        $blocksQuery = ScheduleBlock::with(['technician.user', 'task'])
            ->forDate($date);

        if ($technicianId) {
            $blocksQuery->where('technician_id', $technicianId);
        }

        $scheduleBlocks = $blocksQuery->orderBy('start_time')->get();

        return compact('tasks', 'scheduleBlocks', 'date');
    }


    /**
     * Vista de semana
     */
    protected function getWeekView($date, $technicianId = null)
    {
        $startOfWeek = Carbon::parse($date)->startOfWeek();
        $endOfWeek = Carbon::parse($date)->endOfWeek();

        $query = Task::with(['technician.user', 'serviceRequest', 'sla'])
            ->whereBetween('scheduled_date', [$startOfWeek, $endOfWeek]);

        if ($technicianId) {
            $query->forTechnician($technicianId);
        }

        $tasks = $query->orderBy('scheduled_date')
            ->orderByRaw("CASE WHEN scheduled_order IS NULL OR scheduled_order = 0 THEN 1 ELSE 0 END")
            ->orderBy('scheduled_order')
            ->orderBy('scheduled_start_time')
            ->get()
            ->groupBy(function ($task) {
                return $task->scheduled_date->format('Y-m-d');
            });

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $days[] = [
                'date' => $day,
                'tasks' => $tasks->get($day->format('Y-m-d'), collect()),
            ];
        }

        return compact('days', 'startOfWeek', 'endOfWeek');
    }

    /**
     * Vista de mes
     */
    protected function getMonthView($date, $technicianId = null)
    {
        $startOfMonth = Carbon::parse($date)->startOfMonth();
        $endOfMonth = Carbon::parse($date)->endOfMonth();

        $query = Task::with(['technician.user'])
            ->whereBetween('scheduled_date', [$startOfMonth, $endOfMonth]);

        if ($technicianId) {
            $query->forTechnician($technicianId);
        }

        $tasks = $query->get()
            ->groupBy(function ($task) {
                return $task->scheduled_date->format('Y-m-d');
            });

        // Generar estructura de calendario
        $startDate = $startOfMonth->copy()->startOfWeek();
        $endDate = $endOfMonth->copy()->endOfWeek();

        $weeks = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $currentDate->format('Y-m-d');
                $week[] = [
                    'date' => $currentDate->copy(),
                    'is_current_month' => $currentDate->month === $startOfMonth->month,
                    'tasks' => $tasks->get($dateKey, collect()),
                    'task_count' => $tasks->get($dateKey, collect())->count(),
                ];
                $currentDate->addDay();
            }
            $weeks[] = $week;
        }

        return compact('weeks', 'startOfMonth', 'endOfMonth');
    }

    /**
     * API: Obtener tareas para calendario fullcalendar
     */
    public function getEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $technicianId = $request->get('technician_id');

        $query = Task::with(['technician.user', 'serviceRequest'])
            ->whereBetween('scheduled_date', [$start, $end]);

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        $tasks = $query->get();

        $events = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->scheduled_date->format('Y-m-d') . 'T' . $task->scheduled_time,
                'backgroundColor' => $this->getColorForTask($task),
                'borderColor' => $this->getColorForTask($task),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'task_code' => $task->task_code,
                    'type' => $task->type,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'technician' => $task->technician?->user->name,
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Mover tarea (drag & drop)
     */
    public function moveTask(Request $request, Task $task)
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'technician_id' => 'nullable|exists:technicians,id',
        ]);

        $scheduledOrder = $task->scheduled_order ?? 0;
        $currentDate = optional($task->scheduled_date)->format('Y-m-d');
        $currentTechnicianId = $task->technician_id;
        $newTechnicianId = $validated['technician_id'] ?? $task->technician_id;

        if ($currentDate !== $validated['scheduled_date'] || $currentTechnicianId !== $newTechnicianId) {
            $maxOrder = Task::whereDate('scheduled_date', $validated['scheduled_date'])
                ->where('technician_id', $newTechnicianId)
                ->max('scheduled_order');
            $scheduledOrder = !empty($maxOrder) ? $maxOrder + 1 : 0;
        }

        $task->update(array_merge($validated, [
            'scheduled_order' => $scheduledOrder,
        ]));

        $task->addHistory('rescheduled', auth()->id(), 'Movido desde calendario');

        // Actualizar bloque de horario
        if ($task->scheduleBlock) {
            $task->scheduleBlock->update([
                'block_date' => $validated['scheduled_date'],
                'start_time' => $validated['scheduled_time'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reordenar tareas del día (drag & drop en Mi Agenda)
     */
    public function reorderDayTasks(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|distinct|exists:tasks,id',
            'scheduled_date' => 'required|date',
            'technician_id' => 'nullable|exists:technicians,id',
        ]);

        $user = auth()->user();

        if (!empty($validated['technician_id']) && $user->isAdmin()) {
            $technician = Technician::with('user')->findOrFail($validated['technician_id']);
        } else {
            $technician = Technician::where('user_id', $user->id)->first();
        }

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un perfil de técnico asignado.',
            ], 403);
        }

        $tasks = Task::whereIn('id', $validated['task_ids'])
            ->whereDate('scheduled_date', $validated['scheduled_date'])
            ->where('technician_id', $technician->id)
            ->get()
            ->keyBy('id');

        if ($tasks->count() !== count($validated['task_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron validar todas las tareas.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $tasks) {
            foreach ($validated['task_ids'] as $index => $taskId) {
                $task = $tasks->get((int) $taskId);
                if (!$task) {
                    continue;
                }
                $task->scheduled_order = $index + 1;
                $task->save();
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Dashboard de capacidad del equipo
     */
    public function teamCapacity(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $technicians = Technician::with(['user', 'tasks' => function ($query) use ($date) {
            $query->forDate($date);
        }])->active()->get();

        $teamStats = [
            'total_technicians' => $technicians->count(),
            'total_tasks_today' => Task::forDate($date)->count(),
            'completed_tasks_today' => Task::forDate($date)->where('status', 'completed')->count(),
            'in_progress_tasks_today' => Task::forDate($date)->where('status', 'in_progress')->count(),
            'pending_tasks_today' => Task::forDate($date)->where('status', 'pending')->count(),
        ];

        $capacityData = $technicians->map(function ($tech) use ($date) {
            // Convertir max_daily_capacity_hours a minutos
            $totalCapacity = ($tech->max_daily_capacity_hours ?? 0) * 60;
            // Convertir estimated_hours a minutos
            $usedCapacity = $tech->tasks->sum(function($task) {
                return round($task->estimated_hours * 60);
            });
            $utilization = $totalCapacity > 0 ? ($usedCapacity / $totalCapacity) * 100 : 0;
            $availableCapacity = $totalCapacity > 0 ? max(0, $totalCapacity - $usedCapacity) : 0;
            $isOverAllocated = $totalCapacity > 0 && $usedCapacity > $totalCapacity;

            return [
                'technician' => $tech,
                'total_capacity' => $totalCapacity,
                'used_capacity' => $usedCapacity,
                'available_capacity' => $availableCapacity,
                'utilization_percentage' => round($utilization, 1),
                'is_over_allocated' => $isOverAllocated,
                'tasks_count' => $tech->tasks->count(),
                'status' => $this->getUtilizationStatus($utilization),
            ];
        });

        return view('technician-schedule.team-capacity', compact('capacityData', 'teamStats', 'date'));
    }

    /**
     * Mi agenda (vista del técnico)
     */
    public function myAgenda(Request $request)
    {
        $user = auth()->user();

        // Si se especifica un técnico en la URL y el usuario es admin, mostrar ese técnico
        $technicianId = $request->get('technician_id');

        if ($technicianId && $user->isAdmin()) {
            // Administrador viendo la agenda de otro técnico
            $technician = Technician::with('user')->findOrFail($technicianId);
            $isViewingOther = true;
        } else {
            // Usuario viendo su propia agenda
            $technician = Technician::where('user_id', $user->id)->first();
            $isViewingOther = false;

            if (!$technician) {
                return view('technician-schedule.no-technician-profile', [
                    'user' => $user,
                    'message' => 'No tienes un perfil de técnico asignado. Contacta al administrador para que te asigne uno.'
                ]);
            }
        }

        $date = $request->get('date', now()->format('Y-m-d'));

        $tasks = $technician->getTasksForDate($date);
        $tasks->load(['serviceRequest.subService.service']);
        $scheduleBlocks = $technician->scheduleBlocks()->forDate($date)->orderBy('start_time')->get();

        $stats = [
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'total_estimated_minutes' => $tasks->sum(function($task) {
                return round($task->estimated_hours * 60);
            }),
        ];

        // Obtener lista de técnicos para el selector (solo para admin)
        $technicians = collect();
        if ($user->isAdmin()) {
            $technicians = Technician::with('user')->active()->get();
        }

        $openTasks = Task::query()
            ->with('serviceRequest.subService.service')
            ->where('technician_id', $technician->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNull('scheduled_date')
            ->orderByDesc('updated_at')
            ->get();

        return view('technician-schedule.my-agenda', compact('technician', 'tasks', 'scheduleBlocks', 'date', 'stats', 'isViewingOther', 'technicians', 'openTasks'));
    }

    /**
     * Obtener color según tipo y prioridad de tarea
     */
    protected function getColorForTask($task)
    {
        if ($task->status === 'completed') {
            return '#10b981'; // green
        }

        if ($task->status === 'blocked') {
            return '#ef4444'; // red
        }

        if ($task->type === 'impact') {
            return match($task->priority) {
                'critical' => '#dc2626',
                'high' => '#f97316',
                'medium' => '#f59e0b',
                'low' => '#84cc16',
                default => '#6b7280',
            };
        }

        // Regular
        return match($task->priority) {
            'critical' => '#dc2626',
            'high' => '#f59e0b',
            'medium' => '#3b82f6',
            'low' => '#10b981',
            default => '#6b7280',
        };
    }

    /**
     * Obtener estado de utilización
     */
    protected function getUtilizationStatus($percentage)
    {
        if ($percentage >= 90) {
            return 'overloaded';
        } elseif ($percentage >= 75) {
            return 'high';
        } elseif ($percentage >= 50) {
            return 'optimal';
        } elseif ($percentage >= 25) {
            return 'low';
        }
        return 'underutilized';
    }

    /**
     * Guardar un bloqueo de horario
     */
    public function storeBlock(Request $request)
    {
        $validated = $request->validate([
            'block_date' => 'required|date',
            'block_type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $user = auth()->user();
        $technician = Technician::where('user_id', $user->id)->first();

        if (!$technician && !$user->isAdmin()) {
            return back()->with('error', 'No tienes un perfil de técnico.');
        }

        // Si es admin sin perfil técnico, usar technician_id del request si existe
        $technicianId = $technician?->id ?? $request->input('technician_id');

        if (!$technicianId) {
            return back()->with('error', 'Debes especificar un técnico.');
        }

        ScheduleBlock::create([
            'technician_id' => $technicianId,
            'block_date' => $validated['block_date'],
            'block_type' => $validated['block_type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'color' => ScheduleBlock::$blockTypes[$validated['block_type']]['color'] ?? '#6B7280',
        ]);

        return back()->with('success', 'Bloqueo de horario creado exitosamente.');
    }

    /**
     * Eliminar un bloqueo de horario
     */
    public function destroyBlock(ScheduleBlock $block)
    {
        $user = auth()->user();
        $technician = Technician::where('user_id', $user->id)->first();

        if (!$user->isAdmin() && $block->technician_id !== $technician?->id) {
            return back()->with('error', 'No tienes permiso para eliminar este bloqueo.');
        }

        $block->delete();
        return back()->with('success', 'Bloqueo eliminado.');
    }

    /**
     * Vista Gantt multi-técnico
     */
    public function ganttView(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $technicians = Technician::with('user')->active()->get();

        $tasks = Task::with(['technician.user', 'serviceRequest'])
            ->forDate($date)
            ->orderByRaw("CASE WHEN scheduled_order IS NULL OR scheduled_order = 0 THEN 1 ELSE 0 END")
            ->orderBy('scheduled_order')
            ->orderBy('scheduled_start_time')
            ->get()
            ->groupBy('technician_id');

        $blocks = ScheduleBlock::with('technician')
            ->forDate($date)
            ->get()
            ->groupBy('technician_id');

        $hours = [];
        for ($h = 6; $h <= 18; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        return view('technician-schedule.gantt', compact('technicians', 'tasks', 'blocks', 'date', 'hours'));
    }
}
