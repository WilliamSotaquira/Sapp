<?php

namespace App\Http\Controllers;

use App\Models\OperationalAlert;
use App\Models\ScheduleBlock;
use App\Models\ServiceRequest;
use App\Models\SlaCompliance;
use App\Models\Task;
use App\Models\TaskAlert;
use App\Models\TaskHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MySpaceController extends Controller
{
    /**
     * Mi Espacio — Centro de trabajo personal.
     * Cross-workspace: no depende de session('current_company_id').
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $technician = $user->technician;
        $today = Carbon::today();
        $now = Carbon::now();

        // =====================================================================
        // TAREAS DE HOY (cross-workspace)
        // =====================================================================
        $todayTasks = collect();
        $pendingTasks = collect();
        $overdueTasks = collect();
        $weekTasks = collect();
        $needsEvidenceTasks = collect();

        if ($technician) {
            $todayTasks = Task::where('technician_id', $technician->id)
                ->whereDate('scheduled_date', $today)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['serviceRequest:id,ticket_number,title,company_id', 'serviceRequest.company:id,name', 'subtasks'])
                ->orderByRaw("CASE WHEN scheduled_order IS NULL OR scheduled_order = 0 THEN 1 ELSE 0 END")
                ->orderBy('scheduled_order')
                ->orderBy('scheduled_start_time')
                ->orderBy('priority', 'asc')
                ->get();

            $pendingTasks = Task::where('technician_id', $technician->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->where(function ($q) use ($today) {
                    $q->whereNull('scheduled_date')
                      ->orWhereDate('scheduled_date', '<', $today);
                })
                ->with(['serviceRequest:id,ticket_number,title,company_id', 'serviceRequest.company:id,name'])
                ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
                ->orderBy('due_date')
                ->limit(15)
                ->get();

            $overdueTasks = Task::where('technician_id', $technician->id)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['serviceRequest:id,ticket_number,title,company_id', 'serviceRequest.company:id,name'])
                ->orderBy('due_date')
                ->get();

            $weekTasks = Task::where('technician_id', $technician->id)
                ->whereDate('scheduled_date', '>', $today)
                ->whereDate('scheduled_date', '<=', $today->copy()->addDays(7))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['serviceRequest:id,ticket_number,title,company_id', 'serviceRequest.company:id,name'])
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_order')
                ->get();

            // Tareas que requieren evidencia pero no la tienen
            $needsEvidenceTasks = Task::where('technician_id', $technician->id)
                ->where('requires_evidence', true)
                ->where('evidence_completed', false)
                ->where('status', 'completed')
                ->with(['serviceRequest:id,ticket_number,title,company_id', 'serviceRequest.company:id,name'])
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get();
        }

        // =====================================================================
        // AGENDA VISUAL DEL DÍA (Schedule Blocks)
        // =====================================================================
        $todayBlocks = collect();
        if ($technician) {
            $todayBlocks = ScheduleBlock::where('technician_id', $technician->id)
                ->whereDate('block_date', $today)
                ->blocksOnly()
                ->orderBy('start_time')
                ->get();
        }

        // =====================================================================
        // MIS SOLICITUDES ASIGNADAS (cross-workspace)
        // =====================================================================
        $myServiceRequests = ServiceRequest::withoutGlobalScope('workspace')
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['CERRADA', 'CANCELADA', 'RECHAZADA', 'ARCHIVADA'])
            ->with(['subService:id,name,service_id', 'subService.service:id,name,service_family_id', 'subService.service.family:id,name', 'company:id,name'])
            ->orderByRaw("FIELD(status, 'EN_PROCESO', 'ACEPTADA', 'PENDIENTE', 'PAUSADA', 'REABIERTO', 'RESUELTA')")
            ->orderByRaw("FIELD(criticality_level, 'CRITICA', 'ALTA', 'MEDIA', 'BAJA')")
            ->limit(15)
            ->get();

        // =====================================================================
        // REUNIONES (próximas, hoy, sin asistencia, compromisos)
        // =====================================================================
        $upcomingMeetings = \App\Models\MeetingDetail::query()
            ->where('scheduled_date', '>=', $today)
            ->whereHas('serviceRequest', function ($q) use ($user) {
                $q->withoutGlobalScopes()
                  ->where(function ($sub) use ($user) {
                      // SR asignada al usuario
                      $sub->where('assigned_to', $user->id)
                          // O usuario es participante
                          ->orWhereHas('meetingDetail.participants', function ($pq) use ($user) {
                              $pq->where('user_id', $user->id);
                          });
                  });
            })
            ->with(['serviceRequest' => function ($q) {
                $q->withoutGlobalScopes()->select('id', 'ticket_number', 'title', 'status', 'company_id');
            }, 'serviceRequest.company:id,name', 'participants'])
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        // Reuniones de hoy (para la agenda visual)
        $todayMeetings = $upcomingMeetings->filter(fn($m) => $m->scheduled_date->isToday());

        // Reuniones pasadas sin asistencia marcada
        $meetingsWithoutAttendance = \App\Models\MeetingDetail::query()
            ->where('scheduled_date', '<', $today)
            ->whereHas('serviceRequest', function ($q) use ($user) {
                $q->withoutGlobalScopes()->where('assigned_to', $user->id);
            })
            ->whereHas('participants', function ($q) {
                $q->whereNull('attended');
            })
            ->with(['serviceRequest' => function ($q) {
                $q->withoutGlobalScopes()->select('id', 'ticket_number', 'title', 'company_id');
            }, 'serviceRequest.company:id,name', 'participants'])
            ->orderBy('scheduled_date', 'desc')
            ->limit(5)
            ->get();

        // Compromisos pendientes (Tasks type='impact' asignadas al técnico)
        $pendingCommitments = collect();
        if ($technician) {
            $pendingCommitments = Task::where('technician_id', $technician->id)
                ->where('type', 'impact')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['serviceRequest' => function ($q) {
                    $q->withoutGlobalScopes()->select('id', 'ticket_number', 'title', 'company_id');
                }, 'serviceRequest.company:id,name'])
                ->orderBy('due_date')
                ->limit(10)
                ->get();
        }

        // =====================================================================
        // ACTIVIDAD RECIENTE (Qué hice hoy / últimas 24h)
        // =====================================================================
        $recentActivity = TaskHistory::where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->with('task:id,task_code,title')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // =====================================================================
        // ALERTAS ACTIVAS (cross-workspace)
        // =====================================================================
        $activeAlerts = OperationalAlert::active()
            ->unread()
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('alertable_type', \App\Models\User::class)
                        ->where('alertable_id', $user->id);
                });

                if ($user->technician) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('alertable_type', Task::class)
                            ->whereIn('alertable_id', function ($taskQuery) use ($user) {
                                $taskQuery->select('id')
                                    ->from('tasks')
                                    ->where('technician_id', $user->technician->id);
                            });
                    });
                }

                $companyIds = $user->companies()->pluck('companies.id');
                if ($companyIds->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($companyIds) {
                        $sub->where('alertable_type', ServiceRequest::class)
                            ->whereIn('alertable_id', function ($srQuery) use ($companyIds) {
                                $srQuery->select('id')
                                    ->from('service_requests')
                                    ->whereIn('company_id', $companyIds);
                            });
                    });
                }
            })
            ->orderByRaw("FIELD(severity, 'critica', 'alta', 'media', 'baja')")
            ->orderBy('alert_at', 'desc')
            ->limit(10)
            ->get();

        // =====================================================================
        // ALERTAS DE TAREAS
        // =====================================================================
        $taskAlerts = collect();
        if ($technician) {
            $taskAlerts = TaskAlert::where(function ($q) use ($user, $technician) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('task', function ($tq) use ($technician) {
                          $tq->where('technician_id', $technician->id);
                      });
                })
                ->where('is_dismissed', false)
                ->where('is_read', false)
                ->with('task:id,task_code,title,priority,status')
                ->orderBy('alert_at', 'desc')
                ->limit(10)
                ->get();
        }

        // =====================================================================
        // RECORDATORIOS
        // =====================================================================
        $reminders = OperationalAlert::where('alert_type', OperationalAlert::TYPE_REMINDER)
            ->where('alertable_type', \App\Models\User::class)
            ->where('alertable_id', $user->id)
            ->where('is_resolved', false)
            ->where('is_dismissed', false)
            ->where('alert_at', '<=', $now)
            ->orderBy('alert_at', 'desc')
            ->get();

        $upcomingReminders = OperationalAlert::where('alert_type', OperationalAlert::TYPE_REMINDER)
            ->where('alertable_type', \App\Models\User::class)
            ->where('alertable_id', $user->id)
            ->where('is_resolved', false)
            ->where('is_dismissed', false)
            ->where('alert_at', '>', $now)
            ->orderBy('alert_at')
            ->limit(5)
            ->get();

        // =====================================================================
        // COBERTURA POR FAMILIA DE SERVICIO EN EL CORTE ACTUAL
        // Agrupado por entidad (compañía). Se actualiza dinámicamente según
        // las entidades asociadas al usuario — si se agrega o elimina una,
        // se refleja automáticamente en la próxima carga.
        // =====================================================================
        $coverageByEntity = collect();
        $coverageGlobal = [
            'total_families' => 0,
            'covered_families' => 0,
            'at_risk_families' => 0,
            'total_subservices' => 0,
            'active_subservices' => 0,
            'total_requests_in_cut' => 0,
            'coverage_percentage' => 0,
        ];

        // Compañías del usuario con contrato activo
        // Admin ve todas las compañías; usuario normal ve solo las suyas
        if ($user->isAdmin()) {
            $userCompaniesWithContracts = \App\Models\Company::whereNotNull('active_contract_id')
                ->with(['activeContract'])
                ->get();
        } else {
            $userCompaniesWithContracts = \App\Models\Company::whereIn('id', $user->companies()->pluck('companies.id'))
                ->whereNotNull('active_contract_id')
                ->with(['activeContract'])
                ->get();
        }

        foreach ($userCompaniesWithContracts as $company) {
            $contract = $company->activeContract;
            if (!$contract) continue;

            // Corte abierto del contrato
            $currentCut = \App\Models\Cut::where('contract_id', $contract->id)
                ->open()
                ->first();

            if (!$currentCut) continue;

            // Familias activas del contrato
            $families = \App\Models\ServiceFamily::where('contract_id', $contract->id)
                ->where('is_active', true)
                ->ordered()
                ->get();

            $entityFamilies = collect();

            foreach ($families as $family) {
                $requestsInCut = $currentCut->serviceRequests()
                    ->withoutGlobalScopes()
                    ->whereHas('subService.service', function ($q) use ($family) {
                        $q->where('service_family_id', $family->id);
                    })
                    ->count();

                $subServices = \App\Models\SubService::query()
                    ->whereHas('service', fn($q) => $q->where('service_family_id', $family->id))
                    ->where('is_active', true)
                    ->with('service:id,name')
                    ->get()
                    ->map(function ($sub) use ($currentCut) {
                        $srCount = $currentCut->serviceRequests()
                            ->withoutGlobalScopes()
                            ->where('sub_service_id', $sub->id)
                            ->count();
                        return (object) [
                            'name' => $sub->name,
                            'service_name' => $sub->service?->name,
                            'requests_in_cut' => $srCount,
                            'has_activity' => $srCount > 0,
                        ];
                    });

                $activeSubservices = $subServices->where('has_activity', true)->count();
                $totalSubs = $subServices->count();
                $hasActivity = $requestsInCut > 0;

                $entityFamilies->push((object) [
                    'family_name' => $family->name,
                    'family_code' => $family->code,
                    'total_subservices' => $totalSubs,
                    'active_subservices' => $activeSubservices,
                    'requests_in_cut' => $requestsInCut,
                    'has_activity' => $hasActivity,
                    'subservices' => $subServices,
                ]);

                $coverageGlobal['total_families']++;
                if ($hasActivity) $coverageGlobal['covered_families']++;
                else $coverageGlobal['at_risk_families']++;
                $coverageGlobal['total_subservices'] += $totalSubs;
                $coverageGlobal['active_subservices'] += $activeSubservices;
                $coverageGlobal['total_requests_in_cut'] += $requestsInCut;
            }

            $coverageByEntity->push((object) [
                'company_name' => $company->name,
                'contract_name' => $contract->name ?? $contract->number,
                'cut_name' => $currentCut->name,
                'cut_start' => $currentCut->start_date,
                'cut_end' => $currentCut->end_date,
                'families' => $entityFamilies,
                'total_families' => $entityFamilies->count(),
                'covered_families' => $entityFamilies->where('has_activity', true)->count(),
                'total_requests' => $entityFamilies->sum('requests_in_cut'),
            ]);
        }

        $coverageGlobal['coverage_percentage'] = $coverageGlobal['total_families'] > 0
            ? round(($coverageGlobal['covered_families'] / $coverageGlobal['total_families']) * 100, 0)
            : 0;

        // =====================================================================
        // RESUMEN / KPIs
        // =====================================================================
        $stats = [
            'today_tasks' => $todayTasks->count(),
            'today_completed' => $technician
                ? Task::where('technician_id', $technician->id)
                    ->whereDate('completed_at', $today)
                    ->where('status', 'completed')
                    ->count()
                : 0,
            'pending_tasks' => $pendingTasks->count(),
            'overdue_tasks' => $overdueTasks->count(),
            'active_alerts' => $activeAlerts->count(),
            'reminders_due' => $reminders->count(),
            'week_tasks' => $weekTasks->count(),
            'blocked_tasks' => $technician
                ? Task::where('technician_id', $technician->id)
                    ->where('status', 'blocked')
                    ->count()
                : 0,
            'needs_evidence' => $needsEvidenceTasks->count(),
            'my_srs' => $myServiceRequests->count(),
            'upcoming_meetings' => $upcomingMeetings->count(),
            'pending_commitments' => $pendingCommitments->count(),
            'meetings_without_attendance' => $meetingsWithoutAttendance->count(),
            'coverage_percentage' => $coverageGlobal['coverage_percentage'],
            'at_risk_families' => $coverageGlobal['at_risk_families'],
        ];

        // =====================================================================
        // WORKSPACES del usuario
        // =====================================================================
        $userCompanies = $user->companies()->withPivot('entity_email', 'entity_position')->get();

        return view('my-space.index', compact(
            'todayTasks',
            'pendingTasks',
            'overdueTasks',
            'weekTasks',
            'needsEvidenceTasks',
            'todayBlocks',
            'myServiceRequests',
            'upcomingMeetings',
            'todayMeetings',
            'meetingsWithoutAttendance',
            'pendingCommitments',
            'recentActivity',
            'activeAlerts',
            'taskAlerts',
            'reminders',
            'upcomingReminders',
            'coverageByEntity',
            'coverageGlobal',
            'stats',
            'userCompanies',
            'technician'
        ));
    }

    /**
     * API: Datos actualizados de Mi Día (para refresh sin recarga).
     */
    public function refresh(Request $request)
    {
        $user = auth()->user();
        $technician = $user->technician;
        $today = Carbon::today();

        $stats = [
            'today_tasks' => 0,
            'today_completed' => 0,
            'active_alerts' => 0,
            'reminders_due' => 0,
        ];

        if ($technician) {
            $stats['today_tasks'] = Task::where('technician_id', $technician->id)
                ->whereDate('scheduled_date', $today)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            $stats['today_completed'] = Task::where('technician_id', $technician->id)
                ->whereDate('completed_at', $today)
                ->where('status', 'completed')
                ->count();
        }

        $stats['active_alerts'] = OperationalAlert::active()->unread()
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('alertable_type', \App\Models\User::class)
                        ->where('alertable_id', $user->id);
                });
                if ($user->technician) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('alertable_type', Task::class)
                            ->whereIn('alertable_id', function ($tq) use ($user) {
                                $tq->select('id')->from('tasks')
                                    ->where('technician_id', $user->technician->id);
                            });
                    });
                }
            })
            ->count();

        $stats['reminders_due'] = OperationalAlert::where('alert_type', OperationalAlert::TYPE_REMINDER)
            ->where('alertable_type', \App\Models\User::class)
            ->where('alertable_id', $user->id)
            ->where('is_resolved', false)
            ->where('is_dismissed', false)
            ->where('alert_at', '<=', now())
            ->count();

        return response()->json($stats);
    }

    /**
     * Acción rápida: completar tarea desde Mi Espacio.
     */
    public function completeTask(Request $request, Task $task)
    {
        $user = auth()->user();

        if (!$user->technician || $task->technician_id !== $user->technician->id) {
            abort(403, 'No tienes permiso para completar esta tarea.');
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarea completada.']);
        }

        return back()->with('success', "Tarea \"{$task->title}\" completada.");
    }

    /**
     * Acción rápida: iniciar tarea desde Mi Espacio.
     */
    public function startTask(Request $request, Task $task)
    {
        $user = auth()->user();

        if (!$user->technician || $task->technician_id !== $user->technician->id) {
            abort(403, 'No tienes permiso para iniciar esta tarea.');
        }

        if ($task->status === 'pending' || $task->status === 'confirmed') {
            $task->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarea iniciada.']);
        }

        return back()->with('success', "Tarea \"{$task->title}\" iniciada.");
    }
}
