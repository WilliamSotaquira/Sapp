<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Technician;
use App\Models\ScheduleBlock;
use App\Models\CapacityRule;
use Carbon\Carbon;

class TaskAssignmentService
{
    /**
     * Sugerir técnico para una tarea
     */
    public function suggestTechnicianForTask(Task $task)
    {
        $technicians = Technician::active()->get();
        $suggestions = [];

        foreach ($technicians as $technician) {
            $score = $this->calculateAssignmentScore($technician, $task);

            if ($score > 0) {
                $suggestions[] = [
                    'technician' => $technician,
                    'score' => $score,
                    'available_slots' => $this->getAvailableSlots($technician, $task),
                    'reasons' => $this->getScoreReasons($technician, $task),
                ];
            }
        }

        // Ordenar por score descendente
        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return $suggestions;
    }

    /**
     * Calcular score de asignación (0-100)
     */
    protected function calculateAssignmentScore(Technician $technician, Task $task)
    {
        $score = 0;

        // 1. Skills técnicas (30 puntos)
        $score += $this->calculateSkillScore($technician, $task) * 30;

        // 2. Disponibilidad (25 puntos)
        $score += $this->calculateAvailabilityScore($technician, $task) * 25;

        // 3. Carga actual (20 puntos)
        $score += $this->calculateWorkloadScore($technician, $task) * 20;

        // 4. Experiencia en el proyecto/cliente (15 puntos)
        $score += $this->calculateExperienceScore($technician, $task) * 15;

        // 5. Complejidad vs nivel (10 puntos)
        $score += $this->calculateComplexityMatchScore($technician, $task) * 10;

        return round($score, 2);
    }

    /**
     * Score de skills técnicas (0-1)
     */
    protected function calculateSkillScore(Technician $technician, Task $task)
    {
        if (empty($task->technologies)) {
            return 0.5; // Neutral si no hay tecnologías específicas
        }

        $matchedSkills = 0;
        $totalRequired = count($task->technologies);

        foreach ($task->technologies as $tech) {
            if ($technician->hasSkill($tech)) {
                $matchedSkills++;
            }
        }

        return $totalRequired > 0 ? $matchedSkills / $totalRequired : 0;
    }

    /**
     * Score de disponibilidad (0-1)
     */
    protected function calculateAvailabilityScore(Technician $technician, Task $task)
    {
        $date = $task->scheduled_date ?? now();
        $time = $task->scheduled_time ?? now()->format('H:i');

        // Verificar si está disponible en ese horario
        if (!$this->canAssignTask($technician, $task, $date, $time)) {
            return 0;
        }

        // Calcular % de capacidad disponible
        $availableCapacity = $technician->getAvailableCapacityForDate($date);
        $totalCapacity = $technician->daily_capacity_minutes;

        return $totalCapacity > 0 ? $availableCapacity / $totalCapacity : 0;
    }

    /**
     * Score de carga de trabajo (0-1)
     */
    protected function calculateWorkloadScore(Technician $technician, Task $task)
    {
        $today = now()->format('Y-m-d');
        $tasksToday = $technician->tasks()->forDate($today)->count();

        $rule = CapacityRule::getActiveRuleForTechnician($technician->id);
        $maxTasks = $rule ? ($rule->max_impact_tasks_morning + $rule->max_regular_tasks_afternoon) : 8;

        // Menos tareas = mejor score
        return 1 - ($tasksToday / $maxTasks);
    }

    /**
     * Score de experiencia con el proyecto/cliente (0-1)
     */
    protected function calculateExperienceScore(Technician $technician, Task $task)
    {
        $score = 0;

        // Ha trabajado en el mismo proyecto
        if ($task->project_id) {
            $projectTasks = $technician->tasks()
                ->where('project_id', $task->project_id)
                ->completed()
                ->count();

            if ($projectTasks > 0) {
                $score += 0.5;
            }
        }

        // Ha trabajado con el mismo service request/cliente
        if ($task->service_request_id) {
            $clientTasks = $technician->tasks()
                ->whereHas('serviceRequest', function ($q) use ($task) {
                    $q->where('requested_by', $task->serviceRequest->requested_by);
                })
                ->completed()
                ->count();

            if ($clientTasks > 0) {
                $score += 0.5;
            }
        }

        return min($score, 1);
    }

    /**
     * Score de match complejidad vs nivel (0-1)
     */
    protected function calculateComplexityMatchScore(Technician $technician, Task $task)
    {
        $complexity = $task->technical_complexity ?? 3;

        $levelMap = [
            'junior' => 2,
            'mid' => 3,
            'senior' => 4,
            'lead' => 5,
        ];

        $technicianLevel = $levelMap[$technician->experience_level] ?? 3;

        // Mejor match cuando nivel ≈ complejidad
        $difference = abs($technicianLevel - $complexity);

        return match($difference) {
            0 => 1.0,   // Perfecto match
            1 => 0.7,   // Aceptable
            2 => 0.4,   // Puede funcionar
            default => 0.1, // No ideal
        };
    }

    /**
     * Obtener razones del score
     */
    protected function getScoreReasons(Technician $technician, Task $task)
    {
        $reasons = [];

        // Skills
        if (!empty($task->technologies)) {
            $matched = 0;
            foreach ($task->technologies as $tech) {
                if ($technician->hasSkill($tech)) {
                    $matched++;
                }
            }
            $reasons[] = "Skills: {$matched}/" . count($task->technologies) . " coincidencias";
        }

        // Disponibilidad
        $date = $task->scheduled_date ?? now();
        $available = $technician->getAvailableCapacityForDate($date);
        $reasons[] = "Capacidad disponible: " . round($available / 60, 1) . " horas";

        // Carga actual
        $tasksToday = $technician->tasks()->forDate(now())->count();
        $reasons[] = "Tareas hoy: {$tasksToday}";

        // Experiencia
        if ($task->project_id) {
            $projectTasks = $technician->tasks()
                ->where('project_id', $task->project_id)
                ->completed()
                ->count();

            if ($projectTasks > 0) {
                $reasons[] = "Ha trabajado en este proyecto ({$projectTasks} tareas)";
            }
        }

        return $reasons;
    }

    /**
     * Verificar si se puede asignar tarea
     */
    public function canAssignTask(Technician $technician, Task $task, $date, $time)
    {
        // 1. Verificar estado del técnico
        if ($technician->status !== 'active') {
            return false;
        }

        // 2. Verificar reglas de capacidad
        $rule = CapacityRule::getActiveRuleForTechnician($technician->id);

        if ($rule) {
            $existingTasks = $technician->tasks()->forDate($date)->get();

            // Contar tareas por tipo
            $impactCount = $existingTasks->where('type', 'impact')->count();
            $regularCount = $existingTasks->where('type', 'regular')->count();

            // Verificar límites según tipo de tarea
            if ($task->type === 'impact' && $impactCount >= $rule->max_impact_tasks_morning) {
                return false;
            }

            if ($task->type === 'regular' && $regularCount >= $rule->max_regular_tasks_afternoon) {
                return false;
            }
        }

        // 3. Verificar disponibilidad horaria
        if (!$technician->isAvailableAt($date, $time)) {
            return false;
        }

        // 4. Verificar capacidad total
        $duration = $task->estimated_duration_minutes ?? ($task->type === 'impact' ? 90 : 25);
        $availableCapacity = $technician->getAvailableCapacityForDate($date);

        if ($availableCapacity < $duration) {
            return false;
        }

        return true;
    }

    /**
     * Obtener slots disponibles para una tarea
     */
    public function getAvailableSlots(Technician $technician, Task $task, $daysAhead = 7)
    {
        $slots = [];
        $duration = $task->estimated_duration_minutes ?? ($task->type === 'impact' ? 90 : 25);

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = now()->addDays($i);

            // Obtener bloques ocupados
            $occupiedBlocks = $technician->scheduleBlocks()
                ->forDate($date)
                ->where('status', '!=', 'available')
                ->orderBy('start_time')
                ->get();

            // Generar slots disponibles según tipo de tarea
            if ($task->type === 'impact') {
                $morningSlots = $this->generateMorningSlots($date, $occupiedBlocks, $duration);
                $slots = array_merge($slots, $morningSlots);
            } else {
                $afternoonSlots = $this->generateAfternoonSlots($date, $occupiedBlocks, $duration);
                $slots = array_merge($slots, $afternoonSlots);
            }
        }

        return $slots;
    }

    /**
     * Encontrar el siguiente espacio disponible en la agenda del técnico
     */
    public function findNextAvailableSlot(
        Technician $technician,
        Carbon $preferredStart,
        int $durationMinutes,
        array $options = []
    ): ?Carbon {
        $timezoneName = $preferredStart->getTimezone()->getName();
        $searchDays = $options['search_days'] ?? 5;
        $workStartHour = $options['work_start'] ?? 6;
        $workEndHour = $options['work_end'] ?? 18;
        $slotMinutes = $options['slot_size'] ?? 5;

        $now = Carbon::now($timezoneName);
        if ($preferredStart->lt($now)) {
            $preferredStart = $now->copy();
        }
        $preferredStart = $this->roundToSlot($preferredStart, $slotMinutes);

        for ($dayOffset = 0; $dayOffset <= $searchDays; $dayOffset++) {
            $day = $preferredStart->copy()->addDays($dayOffset);
            $dayStart = $day->copy()->setTime($workStartHour, 0, 0, 0);
            $dayEnd = $day->copy()->setTime($workEndHour, 0, 0, 0);

            $candidate = $dayOffset === 0 ? $preferredStart->copy() : $dayStart->copy();
            if ($candidate->lt($dayStart)) {
                $candidate = $dayStart->copy();
            }
            $candidate = $this->roundToSlot($candidate, $slotMinutes);

            if ($candidate->gt($dayEnd)) {
                continue;
            }

            $dayTasks = $technician->tasks()
                ->whereDate('scheduled_date', $dayStart->format('Y-m-d'))
                ->whereNotIn('status', ['cancelled'])
                ->get()
                ->sortBy(function ($task) {
                    return $task->scheduled_start_time ?? $task->scheduled_time ?? '23:59';
                });

            foreach ($dayTasks as $existingTask) {
                $taskStartRaw = $existingTask->scheduled_start_time ?? $existingTask->scheduled_time;
                if (!$taskStartRaw) {
                    continue;
                }

                if ($taskStartRaw instanceof \DateTimeInterface) {
                    $taskStart = Carbon::instance($taskStartRaw)->setTimezone($timezoneName);
                    $taskStart->setDate(
                        (int) $dayStart->format('Y'),
                        (int) $dayStart->format('m'),
                        (int) $dayStart->format('d')
                    );
                } else {
                    $parsedTime = Carbon::parse((string) $taskStartRaw, $timezoneName);
                    $taskStart = $parsedTime->copy()->setDate(
                        (int) $dayStart->format('Y'),
                        (int) $dayStart->format('m'),
                        (int) $dayStart->format('d')
                    );
                }
                $taskDuration = $existingTask->estimated_duration_minutes
                    ?? ($existingTask->type === 'impact' ? 90 : 25);
                $taskEnd = $taskStart->copy()->addMinutes($taskDuration);

                if ($candidate->copy()->addMinutes($durationMinutes)->lte($taskStart)) {
                    return $candidate;
                }

                if ($candidate->lt($taskEnd)) {
                    $candidate = $taskEnd->copy();
                    $candidate = $this->roundToSlot($candidate, $slotMinutes);
                }

                if ($candidate->gt($dayEnd)) {
                    continue 2;
                }
            }

            if ($candidate->copy()->addMinutes($durationMinutes)->lte($dayEnd)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Generar slots de mañana (tareas de impacto)
     */
    protected function generateMorningSlots($date, $occupiedBlocks, $duration)
    {
        $slots = [];
        $morningSlots = [
            ['start' => '08:15', 'end' => '09:45'],
            ['start' => '10:00', 'end' => '11:30'],
        ];

        foreach ($morningSlots as $slot) {
            $startTime = Carbon::parse("{$date->format('Y-m-d')} {$slot['start']}");
            $endTime = Carbon::parse("{$date->format('Y-m-d')} {$slot['end']}");

            // Verificar si está ocupado
            $isOccupied = $occupiedBlocks->contains(function ($block) use ($startTime, $endTime) {
                $blockStart = Carbon::parse($block->start_time);
                $blockEnd = Carbon::parse($block->end_time);

                return $startTime->lt($blockEnd) && $endTime->gt($blockStart);
            });

            if (!$isOccupied) {
                $slots[] = [
                    'date' => $date->format('Y-m-d'),
                    'time' => $slot['start'],
                    'type' => 'morning_impact',
                ];
            }
        }

        return $slots;
    }

    /**
     * Generar slots de tarde (tareas regulares)
     */
    protected function generateAfternoonSlots($date, $occupiedBlocks, $duration)
    {
        $slots = [];
        $afternoonSlots = [
            '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
        ];

        foreach ($afternoonSlots as $time) {
            $startTime = Carbon::parse("{$date->format('Y-m-d')} {$time}");
            $endTime = $startTime->copy()->addMinutes($duration);

            // Verificar si está ocupado
            $isOccupied = $occupiedBlocks->contains(function ($block) use ($startTime, $endTime) {
                $blockStart = Carbon::parse($block->start_time);
                $blockEnd = Carbon::parse($block->end_time);

                return $startTime->lt($blockEnd) && $endTime->gt($blockStart);
            });

            if (!$isOccupied) {
                $slots[] = [
                    'date' => $date->format('Y-m-d'),
                    'time' => $time,
                    'type' => 'afternoon_regular',
                ];
            }
        }

        return $slots;
    }

    /**
     * Crear bloque de horario para tarea asignada
     */
    public function createScheduleBlock(Task $task)
    {
        if (!$task->technician_id || !$task->scheduled_date || !$task->scheduled_time) {
            return null;
        }

        $duration = $task->estimated_duration_minutes ?? ($task->type === 'impact' ? 90 : 25);
        $endTime = Carbon::parse($task->scheduled_time)->addMinutes($duration);

        $blockType = $task->type === 'impact' ? 'morning_impact' : 'afternoon_regular';

        return ScheduleBlock::create([
            'technician_id' => $task->technician_id,
            'block_date' => $task->scheduled_date,
            'block_type' => $blockType,
            'start_time' => $task->scheduled_time,
            'end_time' => $endTime->format('H:i'),
            'task_id' => $task->id,
            'status' => 'occupied',
            'work_type' => 'focused',
        ]);
    }

    /**
     * Asignación automática (encuentra y asigna el mejor técnico)
     */
    public function autoAssignTask(Task $task)
    {
        $suggestions = $this->suggestTechnicianForTask($task);

        if (empty($suggestions)) {
            return [
                'success' => false,
                'message' => 'No se encontró ningún técnico disponible',
            ];
        }

        $bestMatch = $suggestions[0];
        $technician = $bestMatch['technician'];
        $slots = $bestMatch['available_slots'];

        if (empty($slots)) {
            return [
                'success' => false,
                'message' => 'El mejor técnico no tiene slots disponibles',
            ];
        }

        // Asignar al primer slot disponible
        $firstSlot = $slots[0];

        $task->update([
            'technician_id' => $technician->id,
            'scheduled_date' => $firstSlot['date'],
            'scheduled_time' => $firstSlot['time'],
            'status' => 'pending',
        ]);

        $task->addHistory('assigned', auth()->id(), "Auto-asignado a {$technician->user->name} (Score: {$bestMatch['score']})");

        $this->createScheduleBlock($task);

        return [
            'success' => true,
            'technician' => $technician,
            'slot' => $firstSlot,
            'score' => $bestMatch['score'],
        ];
    }

    protected function roundToSlot(Carbon $time, int $slotMinutes = 5): Carbon
    {
        $rounded = $time->copy();
        $minutes = (int) ceil($rounded->minute / $slotMinutes) * $slotMinutes;
        if ($minutes >= 60) {
            $rounded->addHour();
            $minutes = 0;
        }

        return $rounded->setTime($rounded->hour, $minutes, 0, 0);
    }
}
