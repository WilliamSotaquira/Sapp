<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Informe consolidado del equipo.
 *
 * Refleja el modelo de autoría dual del sistema single-user:
 *  - EJECUCIÓN: qué resolvió cada técnico (assigned_to en las solicitudes).
 *    Es el mérito del equipo; se conserva y se reporta de forma específica.
 *  - CONTROL: qué verificó/controló el líder (tareas type=control completadas
 *    y solicitudes con verified_by). Es la responsabilidad de liderazgo.
 *
 * Ambas caras conviven: el técnico resuelve, el líder verifica y responde ante
 * el contrato.
 */
class TeamConsolidatedController extends Controller
{
    /**
     * Mostrar el informe consolidado de equipo.
     */
    public function index(Request $request): View
    {
        $dateRange = $this->getDateRange($request);

        $executionByTechnician = $this->getExecutionByTechnician($dateRange);
        $controlByLeader = $this->getControlByLeader($dateRange);
        $totals = $this->getTotals($executionByTechnician, $controlByLeader);

        return view('reports.team-consolidated.index', compact(
            'executionByTechnician',
            'controlByLeader',
            'totals',
            'dateRange'
        ));
    }

    /**
     * Exportar el informe consolidado (pdf o csv).
     */
    public function export(Request $request, string $format): Response
    {
        $dateRange = $this->getDateRange($request);

        $executionByTechnician = $this->getExecutionByTechnician($dateRange);
        $controlByLeader = $this->getControlByLeader($dateRange);
        $totals = $this->getTotals($executionByTechnician, $controlByLeader);

        $timestamp = now()->format('Y-m-d_His');

        try {
            if ($format === 'pdf') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.exports.team-consolidated-pdf', compact(
                    'executionByTechnician',
                    'controlByLeader',
                    'totals',
                    'dateRange'
                ));
                $pdf->setPaper('a4', 'portrait');

                return $pdf->download("consolidado-equipo-{$timestamp}.pdf");
            }

            if ($format === 'csv') {
                return $this->exportCsv($executionByTechnician, $controlByLeader, $totals, $dateRange, $timestamp);
            }

            return back()->with('error', 'Formato de exportación no válido. Use pdf o csv.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar la exportación: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATOS
    // =========================================================================

    /**
     * Ejecución por técnico: solicitudes resueltas/cerradas por cada técnico
     * (assigned_to) dentro del período. El "mérito" del equipo.
     */
    private function getExecutionByTechnician(array $dateRange)
    {
        $resolvedStatuses = [
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_CLOSED,
            ServiceRequest::STATUS_NON_VIABLE,
        ];

        return ServiceRequest::query()
            ->reportable()
            ->whereNotNull('service_requests.assigned_to')
            ->whereBetween('service_requests.created_at', [$dateRange['start'], $dateRange['end']])
            ->join('users', 'users.id', '=', 'service_requests.assigned_to')
            ->selectRaw('
                users.id as technician_id,
                users.name as technician_name,
                COUNT(*) as total_assigned,
                SUM(CASE WHEN service_requests.status IN (?, ?, ?) THEN 1 ELSE 0 END) as total_resolved,
                SUM(CASE WHEN service_requests.status = ? THEN 1 ELSE 0 END) as total_closed
            ', [
                ServiceRequest::STATUS_RESOLVED,
                ServiceRequest::STATUS_CLOSED,
                ServiceRequest::STATUS_NON_VIABLE,
                ServiceRequest::STATUS_CLOSED,
            ])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_resolved')
            ->get();
    }

    /**
     * Control del líder: tareas de control completadas y solicitudes verificadas
     * dentro del período, agrupadas por el líder responsable.
     */
    private function getControlByLeader(array $dateRange)
    {
        // Tareas de control completadas por owner (líder).
        $controlTasks = Task::query()
            ->control()
            ->where('status', 'completed')
            ->whereNotNull('owner_id')
            ->whereBetween('completed_at', [$dateRange['start'], $dateRange['end']])
            ->join('users', 'users.id', '=', 'tasks.owner_id')
            ->selectRaw('
                users.id as leader_id,
                users.name as leader_name,
                COUNT(*) as control_tasks_completed
            ')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('leader_id');

        // Solicitudes verificadas por el líder (verified_by) en el período.
        $verified = ServiceRequest::query()
            ->whereNotNull('verified_by')
            ->whereBetween('verified_at', [$dateRange['start'], $dateRange['end']])
            ->join('users', 'users.id', '=', 'service_requests.verified_by')
            ->selectRaw('
                users.id as leader_id,
                users.name as leader_name,
                COUNT(*) as requests_verified
            ')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('leader_id');

        // Fusionar ambas fuentes por líder.
        $leaderIds = $controlTasks->keys()->merge($verified->keys())->unique();

        return $leaderIds->map(function ($leaderId) use ($controlTasks, $verified) {
            $name = $controlTasks[$leaderId]->leader_name
                ?? $verified[$leaderId]->leader_name
                ?? 'Líder';

            return (object) [
                'leader_id' => $leaderId,
                'leader_name' => $name,
                'control_tasks_completed' => (int) ($controlTasks[$leaderId]->control_tasks_completed ?? 0),
                'requests_verified' => (int) ($verified[$leaderId]->requests_verified ?? 0),
            ];
        })->values();
    }

    /**
     * Totales consolidados.
     */
    private function getTotals($executionByTechnician, $controlByLeader): array
    {
        return [
            'technicians' => $executionByTechnician->count(),
            'total_assigned' => (int) $executionByTechnician->sum('total_assigned'),
            'total_resolved' => (int) $executionByTechnician->sum('total_resolved'),
            'control_tasks_completed' => (int) $controlByLeader->sum('control_tasks_completed'),
            'requests_verified' => (int) $controlByLeader->sum('requests_verified'),
        ];
    }

    // =========================================================================
    // EXPORTACIÓN
    // =========================================================================

    private function exportCsv($executionByTechnician, $controlByLeader, array $totals, array $dateRange, string $timestamp): Response
    {
        $csv = "CONSOLIDADO DEL EQUIPO\n";
        $csv .= 'Período: ' . $dateRange['start']->format('d/m/Y') . ' - ' . $dateRange['end']->format('d/m/Y') . "\n\n";

        $csv .= "=== EJECUCIÓN POR TÉCNICO (resuelto por el equipo) ===\n";
        $csv .= "Técnico,Asignadas,Resueltas,Cerradas\n";
        foreach ($executionByTechnician as $row) {
            $csv .= sprintf(
                "\"%s\",%d,%d,%d\n",
                $row->technician_name,
                $row->total_assigned,
                $row->total_resolved,
                $row->total_closed
            );
        }

        $csv .= "\n=== CONTROL DEL LÍDER (verificación/seguimiento) ===\n";
        $csv .= "Líder,Controles completados,Solicitudes verificadas\n";
        foreach ($controlByLeader as $row) {
            $csv .= sprintf(
                "\"%s\",%d,%d\n",
                $row->leader_name,
                $row->control_tasks_completed,
                $row->requests_verified
            );
        }

        $csv .= "\n=== TOTALES ===\n";
        $csv .= "Técnicos con actividad,{$totals['technicians']}\n";
        $csv .= "Solicitudes asignadas,{$totals['total_assigned']}\n";
        $csv .= "Solicitudes resueltas,{$totals['total_resolved']}\n";
        $csv .= "Controles completados,{$totals['control_tasks_completed']}\n";
        $csv .= "Solicitudes verificadas,{$totals['requests_verified']}\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"consolidado-equipo-{$timestamp}.csv\"",
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getDateRange(Request $request): array
    {
        $startInput = $request->input('date_from');
        $endInput = $request->input('date_to');

        $startDate = $startInput
            ? Carbon::parse($startInput)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $endInput
            ? Carbon::parse($endInput)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }
}
