<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Technician;
use App\Models\TaskAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SmartSchedulingService
{
    /**
     * Horarios de trabajo
     */
    const MORNING_START = '08:00';
    const MORNING_END = '12:00';
    const AFTERNOON_START = '13:00';
    const AFTERNOON_END = '17:00';

    /**
     * Unidad de tiempo básica en minutos
     */
    const TIME_BLOCK = 25;

    /**
     * Programa automáticamente una tarea según su criticidad
     * - Tareas críticas → Mañana
     * - Tareas no críticas → Tarde
     */
    public function scheduleTask(Task $task, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::parse($task->scheduled_date ?? now());
        
        // Determinar horario según criticidad
        $timeRange = $task->is_critical 
            ? ['start' => self::MORNING_START, 'end' => self::MORNING_END]
            : ['start' => self::AFTERNOON_START, 'end' => self::AFTERNOON_END];

        // Buscar slot disponible
        $slot = $this->findAvailableSlot($task, $date, $timeRange);

        if (!$slot) {
            // Si no hay espacio en el día, buscar en el siguiente día hábil
            $nextDate = $this->getNextWorkingDay($date);
            $slot = $this->findAvailableSlot($task, $nextDate, $timeRange);
        }

        if ($slot) {
            $task->update([
                'scheduled_date' => $slot['date'],
                'scheduled_start_time' => $slot['time'],
            ]);

            return [
                'success' => true,
                'date' => $slot['date'],
                'time' => $slot['time'],
                'message' => 'Tarea programada exitosamente',
            ];
        }

        return [
            'success' => false,
            'message' => 'No se encontró un horario disponible',
        ];
    }

    /**
     * Encuentra un slot disponible para la tarea
     */
    protected function findAvailableSlot(Task $task, Carbon $date, array $timeRange): ?array
    {
        $technicianId = $task->technician_id;
        $blocksNeeded = $task->time_blocks ?? 1;
        $minutesNeeded = $blocksNeeded * self::TIME_BLOCK;

        // Obtener tareas existentes del técnico para ese día
        $existingTasks = Task::forDate($date)
            ->forTechnician($technicianId)
            ->where('id', '!=', $task->id)
            ->orderBy('scheduled_start_time')
            ->get();

        // Calcular slots ocupados
        $occupiedSlots = $this->getOccupiedSlots($existingTasks);

        // Buscar primer slot disponible
        $currentTime = Carbon::parse($date->format('Y-m-d') . ' ' . $timeRange['start']);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $timeRange['end']);

        while ($currentTime->copy()->addMinutes($minutesNeeded)->lte($endTime)) {
            if ($this->isSlotAvailable($currentTime, $minutesNeeded, $occupiedSlots)) {
                return [
                    'date' => $date->format('Y-m-d'),
                    'time' => $currentTime->format('H:i:00'),
                ];
            }
            $currentTime->addMinutes(self::TIME_BLOCK);
        }

        return null;
    }

    /**
     * Obtiene los slots ocupados por tareas existentes
     */
    protected function getOccupiedSlots(Collection $tasks): array
    {
        $slots = [];
        foreach ($tasks as $task) {
            if ($task->scheduled_start_time) {
                $start = Carbon::parse($task->scheduled_start_time);
                $duration = $task->calculated_duration ?? self::TIME_BLOCK;
                $end = $start->copy()->addMinutes($duration);
                
                $slots[] = ['start' => $start, 'end' => $end];
            }
        }
        return $slots;
    }

    /**
     * Verifica si un slot está disponible
     */
    protected function isSlotAvailable(Carbon $start, int $duration, array $occupiedSlots): bool
    {
        $end = $start->copy()->addMinutes($duration);

        foreach ($occupiedSlots as $slot) {
            // Verificar superposición
            if ($start->lt($slot['end']) && $end->gt($slot['start'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene el siguiente día hábil (no domingo)
     */
    protected function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();
        while ($nextDay->isSunday()) {
            $nextDay->addDay();
        }
        return $nextDay;
    }

    /**
     * Auto-programa todas las tareas pendientes de un técnico
     * Prioriza tareas críticas primero
     */
    public function autoScheduleTechnicianTasks(Technician $technician, Carbon $date): array
    {
        $results = [];

        // Obtener tareas pendientes sin programar
        $pendingTasks = Task::where('technician_id', $technician->id)
            ->whereNull('scheduled_date')
            ->where('status', 'pending')
            ->orderByRaw("CASE WHEN is_critical = 1 THEN 0 ELSE 1 END") // Críticas primero
            ->orderBy('due_date', 'asc') // Luego por fecha de vencimiento
            ->orderBy('priority', 'asc')
            ->get();

        foreach ($pendingTasks as $task) {
            $result = $this->scheduleTask($task, $date);
            $results[] = [
                'task' => $task,
                'result' => $result,
            ];

            // Si una tarea crítica no se pudo programar, generar alerta
            if (!$result['success'] && $task->is_critical) {
                TaskAlert::create([
                    'task_id' => $task->id,
                    'alert_type' => 'critical_pending',
                    'message' => "No se pudo programar la tarea crítica: {$task->title}",
                    'alert_at' => now(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Reorganiza las tareas del día según criticidad
     * Mueve críticas a la mañana y no críticas a la tarde
     */
    public function reorganizeDaySchedule(Technician $technician, Carbon $date): array
    {
        $changes = [];

        // Tareas críticas que están en la tarde → mover a la mañana
        $criticalInAfternoon = Task::forDate($date)
            ->forTechnician($technician->id)
            ->where('is_critical', true)
            ->whereTime('scheduled_start_time', '>=', self::AFTERNOON_START)
            ->get();

        foreach ($criticalInAfternoon as $task) {
            $result = $this->scheduleTask($task, $date);
            if ($result['success']) {
                $changes[] = [
                    'task' => $task,
                    'action' => 'moved_to_morning',
                    'new_time' => $result['time'],
                ];
            }
        }

        // Tareas no críticas que están en la mañana → mover a la tarde
        $nonCriticalInMorning = Task::forDate($date)
            ->forTechnician($technician->id)
            ->where('is_critical', false)
            ->whereTime('scheduled_start_time', '<', self::MORNING_END)
            ->whereTime('scheduled_start_time', '>=', self::MORNING_START)
            ->get();

        // Temporalmente marcar como no críticas para que se programen en la tarde
        foreach ($nonCriticalInMorning as $task) {
            $result = $this->scheduleTask($task, $date);
            if ($result['success']) {
                $changes[] = [
                    'task' => $task,
                    'action' => 'moved_to_afternoon',
                    'new_time' => $result['time'],
                ];
            }
        }

        return $changes;
    }

    /**
     * Genera alertas para tareas críticas
     */
    public function generateCriticalTaskAlerts(): int
    {
        $alertsCreated = 0;

        // Alertas de tareas próximas a vencer (24 horas)
        TaskAlert::generateDueSoonAlerts();
        
        // Alertas de tareas vencidas
        TaskAlert::generateOverdueAlerts();

        // Alertas de tareas sin evidencia completadas
        $tasksWithoutEvidence = Task::where('status', 'completed')
            ->where('requires_evidence', true)
            ->where('evidence_completed', false)
            ->get();

        foreach ($tasksWithoutEvidence as $task) {
            $exists = TaskAlert::where('task_id', $task->id)
                ->where('alert_type', 'no_evidence')
                ->where('is_dismissed', false)
                ->exists();

            if (!$exists) {
                TaskAlert::create([
                    'task_id' => $task->id,
                    'alert_type' => 'no_evidence',
                    'message' => "La tarea '{$task->title}' está completada pero falta evidencia",
                    'alert_at' => now(),
                ]);
                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Obtiene estadísticas de programación del día
     */
    public function getDayStats(Carbon $date, ?int $technicianId = null): array
    {
        $query = Task::forDate($date);
        
        if ($technicianId) {
            $query->forTechnician($technicianId);
        }

        $tasks = $query->get();

        return [
            'total_tasks' => $tasks->count(),
            'critical_tasks' => $tasks->where('is_critical', true)->count(),
            'non_critical_tasks' => $tasks->where('is_critical', false)->count(),
            'morning_tasks' => $tasks->filter(fn($t) => $t->scheduled_start_time && Carbon::parse($t->scheduled_start_time)->format('H') < 12)->count(),
            'afternoon_tasks' => $tasks->filter(fn($t) => $t->scheduled_start_time && Carbon::parse($t->scheduled_start_time)->format('H') >= 12)->count(),
            'total_time_blocks' => $tasks->sum('time_blocks'),
            'completed' => $tasks->where('status', 'completed')->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'overdue' => $tasks->where('is_overdue', true)->count(),
        ];
    }
}
