<?php

namespace App\Http\Controllers;

use App\Models\Requirement;
use App\Models\Project;
use App\Models\Alert;
use App\Models\Task;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events(Request $request)
    {
        $events = [];

        // Requerimientos
        $requirements = Requirement::whereNotNull('due_date')->get();
        foreach ($requirements as $requirement) {
            $events[] = [
                'id' => 'req-' . $requirement->id,
                'title' => '📋 ' . $requirement->title,
                'start' => $requirement->due_date->format('Y-m-d'),
                'color' => $this->getRequirementColor($requirement),
                'url' => route('requirements.show', $requirement->id),
                'extendedProps' => [
                    'type' => 'requirement',
                    'priority' => $requirement->priority,
                    'status' => $requirement->status,
                    'code' => $requirement->code
                ]
            ];
        }

        // Proyectos
        $projects = Project::whereNotNull('end_date')->get();
        foreach ($projects as $project) {
            $events[] = [
                'id' => 'proj-' . $project->id,
                'title' => '🚀 ' . $project->name,
                'start' => $project->start_date->format('Y-m-d'),
                'end' => $project->end_date->addDay()->format('Y-m-d'), // +1 día para incluir el último día
                'color' => $this->getProjectColor($project),
                'url' => route('projects.show', $project->id),
                'extendedProps' => [
                    'type' => 'project',
                    'status' => $project->status,
                    'code' => $project->code
                ]
            ];
        }

        // Alertas
        $alerts = Alert::where('is_active', true)->get();
        foreach ($alerts as $alert) {
            $events[] = [
                'id' => 'alert-' . $alert->id,
                'title' => '⚠️ ' . $alert->title,
                'start' => $alert->alert_date->format('Y-m-d'),
                'color' => $this->getAlertColor($alert),
                'extendedProps' => [
                    'type' => 'alert',
                    'alert_type' => $alert->type,
                    'message' => $alert->message
                ]
            ];
        }

        // Tareas (confirmadas y programadas)
        $tasks = Task::with(['technician.user'])
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereNotNull('scheduled_date')
            ->get();

        foreach ($tasks as $task) {
            // Calcular fecha de fin basada en la duración estimada
            $startDateTime = \Carbon\Carbon::parse($task->scheduled_date->format('Y-m-d') . ' ' . $task->scheduled_start_time);
            $endDateTime = $startDateTime->copy()->addHours($task->estimated_hours);

            $events[] = [
                'id' => 'task-' . $task->id,
                'title' => '🔧 ' . $task->title,
                'start' => $startDateTime->format('Y-m-d\TH:i:s'),
                'end' => $endDateTime->format('Y-m-d\TH:i:s'),
                'color' => $this->getTaskColor($task),
                'url' => route('tasks.show', $task->id),
                'extendedProps' => [
                    'type' => 'task',
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'technician' => $task->technician?->user?->name,
                    'estimated_hours' => $task->estimated_hours
                ]
            ];
        }

        return response()->json($events);
    }

    private function getRequirementColor($requirement)
    {
        return match($requirement->priority) {
            'urgent' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#28a745',
            default => '#007bff'
        };
    }

    private function getProjectColor($project)
    {
        return match($project->status) {
            'completed' => '#28a745',
            'cancelled' => '#6c757d',
            'on_hold' => '#ffc107',
            default => '#007bff'
        };
    }

    private function getAlertColor($alert)
    {
        return match($alert->type) {
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            'success' => '#28a745',
            default => '#17a2b8'
        };
    }

    private function getTaskColor($task)
    {
        // Color por prioridad si la tarea está confirmada
        if ($task->status === 'confirmed') {
            return match($task->priority) {
                'urgent' => '#dc3545',
                'high' => '#fd7e14',
                'medium' => '#ffc107',
                'low' => '#20c997',
                default => '#007bff'
            };
        }

        // Color por estado si está en progreso
        return match($task->status) {
            'in_progress' => '#17a2b8',
            'completed' => '#28a745',
            'cancelled' => '#6c757d',
            default => '#007bff'
        };
    }
}
