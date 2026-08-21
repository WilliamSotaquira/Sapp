<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;

/**
 * Servicio de generación de informes consolidados de proyecto.
 *
 * Recopila datos de todas las solicitudes vinculadas al proyecto para
 * generar un informe auditable con: solicitudes, tareas, tiempos, evidencias.
 */
class ProjectReportService
{
    /**
     * Generar datos del informe consolidado.
     */
    public function generate(Project $project): array
    {
        $project->load([
            'serviceRequests' => function ($q) {
                $q->with([
                    'subService',
                    'requester',
                    'assignee',
                    'tasks.subtasks',
                    'evidences' => fn ($eq) => $eq->whereIn('evidence_type', ['ARCHIVO', 'ENLACE', 'PASO_A_PASO', 'ACTA']),
                    'statusHistories',
                ]);
            },
            'creator',
        ]);

        $serviceRequests = $project->serviceRequests;

        // Métricas generales
        $totalRequests = $serviceRequests->count();
        $resolvedRequests = $serviceRequests->whereIn('status', ['RESUELTA', 'CERRADA'])->count();
        $activeRequests = $serviceRequests->whereNotIn('status', ['RESUELTA', 'CERRADA', 'CANCELADA'])->count();

        // Tareas
        $allTasks = $serviceRequests->flatMap->tasks;
        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->where('status', 'completed')->count();
        $totalMinutes = (int) $allTasks->sum('actual_duration_minutes');
        $estimatedMinutes = (int) $allTasks->sum('estimated_duration_minutes');

        // Evidencias
        $allEvidences = $serviceRequests->flatMap->evidences;

        // Línea de tiempo
        $firstCreated = $serviceRequests->min('created_at');
        $lastResolved = $serviceRequests->max('resolved_at');

        return [
            'project' => [
                'name' => $project->name,
                'code' => $project->code,
                'description' => $project->description,
                'status' => $project->status_label,
                'start_date' => $project->start_date?->format('d/m/Y'),
                'expected_end_date' => $project->expected_end_date?->format('d/m/Y'),
                'actual_end_date' => $project->actual_end_date?->format('d/m/Y'),
                'created_by' => $project->creator?->name,
                'progress' => $project->progress,
            ],
            'summary' => [
                'total_requests' => $totalRequests,
                'resolved_requests' => $resolvedRequests,
                'active_requests' => $activeRequests,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'total_hours' => round($totalMinutes / 60, 1),
                'estimated_hours' => round($estimatedMinutes / 60, 1),
                'total_evidences' => $allEvidences->count(),
                'first_activity' => $firstCreated ? Carbon::parse($firstCreated)->format('d/m/Y') : null,
                'last_resolution' => $lastResolved ? Carbon::parse($lastResolved)->format('d/m/Y') : null,
            ],
            'requests' => $serviceRequests->map(function ($sr) {
                return [
                    'ticket' => $sr->ticket_number,
                    'title' => $sr->title,
                    'status' => $sr->status,
                    'sub_service' => $sr->subService?->name,
                    'requester' => $sr->requester?->name,
                    'assigned_to' => $sr->assignee?->name,
                    'criticality' => $sr->criticality_level,
                    'created_at' => $sr->created_at?->format('d/m/Y'),
                    'resolved_at' => $sr->resolved_at?->format('d/m/Y'),
                    'tasks_count' => $sr->tasks->count(),
                    'tasks_completed' => $sr->tasks->where('status', 'completed')->count(),
                    'hours_spent' => round($sr->tasks->sum('actual_duration_minutes') / 60, 1),
                    'evidences_count' => $sr->evidences->count(),
                    'tasks' => $sr->tasks->map(fn ($t) => [
                        'code' => $t->task_code,
                        'title' => $t->title,
                        'status' => $t->status,
                        'estimated_hours' => $t->estimated_hours,
                        'actual_hours' => $t->actual_hours,
                        'completed_at' => $t->completed_at?->format('d/m/Y H:i'),
                    ])->toArray(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Exportar informe como CSV.
     */
    public function exportCsv(Project $project): string
    {
        $data = $this->generate($project);
        $lines = [];

        // Encabezado del proyecto
        $lines[] = "INFORME DE PROYECTO";
        $lines[] = "Nombre," . $this->csvEscape($data['project']['name']);
        $lines[] = "Código," . $data['project']['code'];
        $lines[] = "Estado," . $data['project']['status'];
        $lines[] = "Descripción," . $this->csvEscape($data['project']['description'] ?? '');
        $lines[] = "Inicio," . ($data['project']['start_date'] ?? '');
        $lines[] = "Fin estimado," . ($data['project']['expected_end_date'] ?? '');
        $lines[] = "Creado por," . ($data['project']['created_by'] ?? '');
        $lines[] = "Progreso," . $data['project']['progress'] . "%";
        $lines[] = "";

        // Resumen
        $lines[] = "RESUMEN";
        $lines[] = "Total solicitudes," . $data['summary']['total_requests'];
        $lines[] = "Resueltas," . $data['summary']['resolved_requests'];
        $lines[] = "Activas," . $data['summary']['active_requests'];
        $lines[] = "Total tareas," . $data['summary']['total_tasks'];
        $lines[] = "Tareas completadas," . $data['summary']['completed_tasks'];
        $lines[] = "Horas invertidas," . $data['summary']['total_hours'];
        $lines[] = "Horas estimadas," . $data['summary']['estimated_hours'];
        $lines[] = "Evidencias," . $data['summary']['total_evidences'];
        $lines[] = "Primera actividad," . ($data['summary']['first_activity'] ?? '');
        $lines[] = "Última resolución," . ($data['summary']['last_resolution'] ?? '');
        $lines[] = "";

        // Detalle de solicitudes
        $lines[] = "DETALLE DE SOLICITUDES";
        $lines[] = "Ticket,Título,Estado,Subservicio,Solicitante,Asignado,Criticidad,Creada,Resuelta,Tareas,Completadas,Horas,Evidencias";

        foreach ($data['requests'] as $req) {
            $lines[] = implode(',', [
                $req['ticket'],
                $this->csvEscape($req['title']),
                $req['status'],
                $this->csvEscape($req['sub_service'] ?? ''),
                $this->csvEscape($req['requester'] ?? ''),
                $this->csvEscape($req['assigned_to'] ?? ''),
                $req['criticality'] ?? '',
                $req['created_at'] ?? '',
                $req['resolved_at'] ?? '',
                $req['tasks_count'],
                $req['tasks_completed'],
                $req['hours_spent'],
                $req['evidences_count'],
            ]);
        }

        $lines[] = "";

        // Detalle de tareas
        $lines[] = "DETALLE DE TAREAS";
        $lines[] = "Solicitud,Código Tarea,Título,Estado,Horas Estimadas,Horas Reales,Completada";

        foreach ($data['requests'] as $req) {
            foreach ($req['tasks'] as $task) {
                $lines[] = implode(',', [
                    $req['ticket'],
                    $task['code'] ?? '',
                    $this->csvEscape($task['title']),
                    $task['status'],
                    $task['estimated_hours'] ?? '',
                    $task['actual_hours'] ?? '',
                    $task['completed_at'] ?? '',
                ]);
            }
        }

        return implode("\n", $lines);
    }

    private function csvEscape(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Si contiene comas, comillas o saltos de línea, envolver en comillas
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
