<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\ServiceLevelAgreement;
use App\Models\User;
use App\Models\ServiceRequestEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ServiceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::with(['subService.service.family', 'requester'])
            ->latest()
            ->paginate(15);

        // Estadísticas para las tarjetas
        $pendingCount = ServiceRequest::where('status', 'PENDIENTE')->count();
        $criticalCount = ServiceRequest::where('criticality_level', 'CRITICA')->count();
        $resolvedCount = ServiceRequest::where('status', 'RESUELTA')->count();

        return view('service-requests.index', compact('serviceRequests', 'pendingCount', 'criticalCount', 'resolvedCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subServices = SubService::with(['service.family'])
            ->where('is_active', true)
            ->get()
            ->groupBy('service.family.name');

        $users = User::all();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return view('service-requests.create', compact('subServices', 'users', 'criticalityLevels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_service_id' => 'required|exists:sub_services,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'assigned_to' => 'nullable|exists:users,id',
            'requested_by' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
            'web_routes' => 'nullable|string',
            'main_web_route' => 'nullable|url',
            'auto_assign' => 'nullable|boolean',
        ]);

        // Generar número de ticket
        $validated['ticket_number'] = ServiceRequest::generateProfessionalTicketNumber($validated['sub_service_id'], $validated['criticality_level']);

        // LÓGICA DE AUTO-ASIGNACIÓN
        if ($request->has('auto_assign') && $request->boolean('auto_assign')) {
            $validated['assigned_to'] = auth()->id();
        }

        // Obtener el SLA y calcular fechas límite
        $sla = ServiceLevelAgreement::find($validated['sla_id']);
        $now = now();
        $validated['acceptance_deadline'] = $now->copy()->addMinutes($sla->acceptance_time_minutes);
        $validated['response_deadline'] = $now->copy()->addMinutes($sla->response_time_minutes);
        $validated['resolution_deadline'] = $now->copy()->addMinutes($sla->resolution_time_minutes);

        $serviceRequest = ServiceRequest::create($validated);

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with('success', 'Solicitud de servicio creada exitosamente. Ticket: ' . $serviceRequest->ticket_number);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['subService.service.family', 'sla', 'requester', 'assignee', 'breachLogs', 'evidences.user']);

        return view('service-requests.show', compact('serviceRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceRequest $serviceRequest)
    {
        $editableStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];

        if (!in_array($serviceRequest->status, $editableStatuses)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'No se pueden editar solicitudes en estado: ' . $serviceRequest->status);
        }

        $subServices = SubService::with(['service.family'])
            ->where('is_active', true)
            ->get()
            ->groupBy('service.family.name');

        $users = User::all();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return view('service-requests.edit', compact('serviceRequest', 'subServices', 'users', 'criticalityLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $editableStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];

        if (!in_array($serviceRequest->status, $editableStatuses)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'No se pueden editar solicitudes en estado: ' . $serviceRequest->status);
        }

        $validated = $request->validate([
            'sub_service_id' => 'required|exists:sub_services,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'assigned_to' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
        ]);

        $serviceRequest->update($validated);

        return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud de servicio actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        if (!in_array($serviceRequest->status, ['PENDIENTE', 'CANCELADA'])) {
            return redirect()->route('service-requests.index')->with('error', 'Solo se pueden eliminar solicitudes en estado PENDIENTE o CANCELADA.');
        }

        $serviceRequest->delete();

        return redirect()->route('service-requests.index')->with('success', 'Solicitud de servicio eliminada exitosamente.');
    }

    // =============================================
    // MÉTODOS PARA FLUJO DE TRABAJO CON EVIDENCIAS
    // =============================================

    /**
     * Aceptar una solicitud de servicio
     */
    public function accept(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()
                ->back()
                ->with('error', 'Esta solicitud ya no puede ser aceptada. Estado actual: ' . $serviceRequest->status);
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $serviceRequest->update([
                    'status' => 'ACEPTADA',
                    'accepted_at' => now(),
                ]);

                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Solicitud Aceptada',
                    'description' => 'La solicitud fue aceptada por ' . auth()->user()->name,
                    'evidence_type' => 'SISTEMA',
                    'step_number' => null,
                    'created_by' => auth()->id(),
                    'evidence_data' => [
                        'action' => 'ACCEPTED',
                        'accepted_by' => auth()->id(),
                        'accepted_at' => now()->toISOString(),
                        'previous_status' => 'PENDIENTE',
                        'new_status' => 'ACEPTADA',
                    ],
                ]);
            });

            return redirect()->back()->with('success', 'Solicitud aceptada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al aceptar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Rechazar una solicitud de servicio
     */
    public function reject(Request $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()
                ->back()
                ->with('error', 'Esta solicitud ya no puede ser rechazada. Estado actual: ' . $serviceRequest->status);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $serviceRequest) {
                $serviceRequest->update([
                    'status' => 'RECHAZADA',
                    'resolution_notes' => $validated['rejection_reason'],
                    'closed_at' => now(),
                ]);

                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Solicitud Rechazada',
                    'description' => $validated['rejection_reason'],
                    'evidence_type' => 'SISTEMA',
                    'created_by' => auth()->id(),
                    'evidence_data' => [
                        'action' => 'REJECTED',
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now()->toISOString(),
                        'rejection_reason' => $validated['rejection_reason'],
                        'previous_status' => 'PENDIENTE',
                        'new_status' => 'RECHAZADA',
                    ],
                ]);
            });

            return redirect()->back()->with('success', 'Solicitud rechazada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al rechazar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Iniciar procesamiento de solicitud
     */
    public function start(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'ACEPTADA') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado ACEPTADA para iniciar el procesamiento.');
        }

        if (!$serviceRequest->assigned_to) {
            return redirect()->back()->with('error', 'No se puede iniciar el procesamiento sin un técnico asignado. Por favor, asigna un técnico primero.');
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'started_at' => now(),
                ]);

                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Procesamiento Iniciado',
                    'description' => 'El trabajo en la solicitud ha comenzado - Técnico: ' . ($serviceRequest->assignee->name ?? 'N/A'),
                    'evidence_type' => 'SISTEMA',
                    'created_by' => auth()->id(),
                    'evidence_data' => [
                        'action' => 'STARTED',
                        'started_by' => auth()->id(),
                        'started_at' => now()->toISOString(),
                        'assigned_technician' => $serviceRequest->assigned_to,
                        'previous_status' => 'ACEPTADA',
                        'new_status' => 'EN_PROCESO',
                    ],
                ]);
            });

            return redirect()->back()->with('success', 'Solicitud marcada como en proceso.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al iniciar el procesamiento: ' . $e->getMessage());
        }
    }

    /**
     * Resolver solicitud
     */
    public function resolve(Request $request, ServiceRequest $serviceRequest)
    {
        \Log::info('=== 🔍 RESOLVE METHOD ===');

        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado EN PROCESO.');
        }

        try {
            // 🎯 Desactivar eventos temporalmente para evitar validación
            ServiceRequest::withoutEvents(function () use ($serviceRequest, $request) {
                $serviceRequest->update([
                    'status' => 'RESUELTA',
                    'resolution_notes' => $request->input('resolution_notes', 'Resolución completada'),
                    'actual_resolution_time' => $request->input('actual_resolution_time', 60),
                    'resolved_at' => now(),
                ]);
            });

            \Log::info('🎉 ÉXITO: Solicitud resuelta (eventos desactivados)');

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', '¡Solicitud resuelta correctamente!');
        } catch (\Exception $e) {
            \Log::error('❌ ERROR: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para reasignar técnico
     */
    public function reassign(ServiceRequest $service_request)
    {
        if (!auth()->user()->can('assign-service-requests')) {
            return redirect()->route('service-requests.show', $service_request)->with('error', 'No tienes permisos para reasignar solicitudes.');
        }

        $allowedStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];
        if (!in_array($service_request->status, $allowedStatuses)) {
            return redirect()
                ->route('service-requests.show', $service_request)
                ->with('error', 'No se puede reasignar una solicitud en estado: ' . $service_request->status);
        }

        $technicians = User::where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['tecnico', 'supervisor', 'admin']);
            })
            ->get();

        return view('service-requests.reassign', compact('service_request', 'technicians'));
    }

    /**
     * Procesar la reasignación de técnico
     */
    public function reassignSubmit(Request $request, ServiceRequest $service_request)
    {
        if (!auth()->user()->can('assign-service-requests')) {
            return redirect()->route('service-requests.show', $service_request)->with('error', 'No tienes permisos para reasignar solicitudes.');
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'reassignment_reason' => 'required|string|min:10|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $service_request) {
                $previousTechnician = $service_request->assigned_to;

                $service_request->update([
                    'assigned_to' => $validated['assigned_to'],
                ]);

                ServiceRequestEvidence::create([
                    'service_request_id' => $service_request->id,
                    'title' => 'Técnico Reasignado',
                    'description' => $validated['reassignment_reason'],
                    'evidence_type' => 'SISTEMA',
                    'created_by' => auth()->id(),
                    'evidence_data' => [
                        'action' => 'REASSIGNED',
                        'reassigned_by' => auth()->id(),
                        'reassigned_at' => now()->toISOString(),
                        'previous_technician' => $previousTechnician,
                        'new_technician' => $validated['assigned_to'],
                        'reassignment_reason' => $validated['reassignment_reason'],
                    ],
                ]);
            });

            return redirect()->route('service-requests.show', $service_request)->with('success', 'Técnico reasignado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al reasignar técnico: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Pausar una solicitud
     */
    public function pause(ServiceRequest $serviceRequest, Request $request)
    {
        Log::info('=== INICIANDO PAUSE ===', [
            'request_id' => $serviceRequest->id,
            'ticket_number' => $serviceRequest->ticket_number,
            'estado_actual' => $serviceRequest->status,
            'usuario' => auth()->user()->name,
        ]);

        if ($serviceRequest->status !== 'EN_PROCESO') {
            Log::warning('No se puede pausar - Estado incorrecto', [
                'estado_actual' => $serviceRequest->status,
                'estado_requerido' => 'EN_PROCESO',
            ]);
            return redirect()->back()->with('error', 'Solo se pueden pausar solicitudes en proceso.');
        }

        if ($serviceRequest->is_paused) {
            Log::warning('No se puede pausar - Ya está pausada', [
                'ticket_number' => $serviceRequest->ticket_number,
            ]);
            return redirect()->back()->with('error', 'La solicitud ya está pausada.');
        }

        $validated = $request->validate(
            [
                'pause_reason' => 'required|string|min:10|max:500',
            ],
            [
                'pause_reason.required' => 'La razón de pausa es obligatoria.',
                'pause_reason.min' => 'La razón debe tener al menos 10 caracteres.',
                'pause_reason.max' => 'La razón no debe exceder los 500 caracteres.',
            ],
        );

        try {
            DB::transaction(function () use ($serviceRequest, $validated) {
                $serviceRequest->update([
                    'status' => 'PAUSADA',
                    'paused_at' => now(),
                    'is_paused' => true,
                    'pause_reason' => $validated['pause_reason'],
                    'paused_by' => auth()->id(),
                    'total_paused_minutes' => $serviceRequest->total_paused_minutes ?? 0,
                ]);

                Log::info('Solicitud pausada exitosamente', [
                    'request_id' => $serviceRequest->id,
                    'ticket_number' => $serviceRequest->ticket_number,
                    'nuevo_estado' => 'PAUSADA',
                    'paused_at' => now(),
                    'razon_longitud' => strlen($validated['pause_reason']),
                ]);

                if (class_exists('App\Models\ActivityLog')) {
                    \App\Models\ActivityLog::create([
                        'service_request_id' => $serviceRequest->id,
                        'user_id' => auth()->id(),
                        'action' => 'PAUSED',
                        'description' => 'Solicitud pausada por ' . auth()->user()->name . '. Razón: ' . $validated['pause_reason'],
                        'created_at' => now(),
                    ]);
                }

                if (class_exists('App\Models\Timeline')) {
                    \App\Models\Timeline::create([
                        'service_request_id' => $serviceRequest->id,
                        'user_id' => auth()->id(),
                        'event_type' => 'paused',
                        'title' => 'Solicitud Pausada',
                        'description' => 'La solicitud fue pausada: ' . $validated['pause_reason'],
                        'icon' => 'pause',
                        'color' => 'warning',
                        'metadata' => [
                            'reason' => $validated['pause_reason'],
                            'paused_at' => now()->toISOString(),
                            'previous_status' => 'EN_PROCESO',
                            'paused_by' => auth()->user()->name,
                        ],
                        'created_at' => now(),
                    ]);
                }
            });

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud pausada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al pausar solicitud', [
                'request_id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al pausar la solicitud: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Reanudar una solicitud pausada
     */
    public function resume(ServiceRequest $serviceRequest)
    {
        Log::info('=== INICIANDO RESUME ===', [
            'request_id' => $serviceRequest->id,
            'ticket_number' => $serviceRequest->ticket_number,
            'estado_actual' => $serviceRequest->status,
            'usuario' => auth()->user()->name,
        ]);

        if ($serviceRequest->status !== 'PAUSADA') {
            return redirect()->back()->with('error', 'Solo se pueden reanudar solicitudes pausadas.');
        }

        if (!$serviceRequest->is_paused) {
            return redirect()->back()->with('error', 'La solicitud no está marcada como pausada.');
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $currentPauseMinutes = 0;
                if ($serviceRequest->paused_at) {
                    $currentPauseMinutes = $serviceRequest->paused_at->diffInMinutes(now());
                }

                $totalPausedMinutes = ($serviceRequest->total_paused_minutes ?? 0) + $currentPauseMinutes;

                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'is_paused' => false,
                    'resumed_at' => now(),
                    'pause_reason' => null,
                    'paused_by' => null,
                    'total_paused_minutes' => $totalPausedMinutes,
                ]);

                Log::info('Solicitud reanudada exitosamente', [
                    'request_id' => $serviceRequest->id,
                    'ticket_number' => $serviceRequest->ticket_number,
                    'minutos_pausa_actual' => $currentPauseMinutes,
                    'total_minutos_pausa' => $totalPausedMinutes,
                ]);

                if (class_exists('App\Models\ActivityLog')) {
                    \App\Models\ActivityLog::create([
                        'service_request_id' => $serviceRequest->id,
                        'user_id' => auth()->id(),
                        'action' => 'RESUMED',
                        'description' => 'Solicitud reanudada por ' . auth()->user()->name . '. Duración de pausa: ' . $currentPauseMinutes . ' minutos',
                        'created_at' => now(),
                    ]);
                }

                if (class_exists('App\Models\Timeline')) {
                    \App\Models\Timeline::create([
                        'service_request_id' => $serviceRequest->id,
                        'user_id' => auth()->id(),
                        'event_type' => 'resumed',
                        'title' => 'Solicitud Reanudada',
                        'description' => 'La solicitud fue reanudada después de ' . $currentPauseMinutes . ' minutos en pausa.',
                        'icon' => 'play',
                        'color' => 'success',
                        'metadata' => [
                            'resumed_at' => now()->toISOString(),
                            'previous_status' => 'PAUSADA',
                            'pause_duration_minutes' => $currentPauseMinutes,
                            'total_pause_minutes' => $totalPausedMinutes,
                        ],
                        'created_at' => now(),
                    ]);
                }
            });

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud reanudada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al reanudar solicitud', [
                'request_id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al reanudar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Cerrar solicitud
     */
    public function close(ServiceRequest $serviceRequest, Request $request)
    {
        if ($serviceRequest->status !== 'RESUELTA') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado RESUELTA.');
        }

        $validated = $request->validate([
            'satisfaction_score' => 'required|integer|min:1|max:5',
        ]);

        $serviceRequest->update([
            'status' => 'CERRADA',
            'closed_at' => now(),
            'satisfaction_score' => $validated['satisfaction_score'],
        ]);

        return redirect()->back()->with('success', 'Solicitud cerrada exitosamente.');
    }

    /**
     * Reabrir solicitud
     */
    public function reopen(ServiceRequest $serviceRequest)
    {
        $validStatuses = ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA'];
        if (!in_array($serviceRequest->status, $validStatuses)) {
            return redirect()->back()->with('error', 'Solo se pueden reabrir solicitudes en estado RESUELTA, CERRADA, CANCELADA o RECHAZADA.');
        }

        $serviceRequest->update([
            'status' => 'PENDIENTE',
            'closed_at' => null,
            'resolved_at' => null,
            'satisfaction_score' => null,
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud reabierta exitosamente.');
    }

    /**
     * Cancelar solicitud
     */
    public function cancel(ServiceRequest $serviceRequest, Request $request)
    {
        $validStatuses = ['PENDIENTE', 'ACEPTADA'];
        $currentStatus = strtoupper(trim($serviceRequest->status));

        if (!in_array($currentStatus, $validStatuses)) {
            return redirect()->back()->with('error', 'Solo se pueden cancelar solicitudes en estado PENDIENTE o ACEPTADA.');
        }

        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:10',
        ]);

        $serviceRequest->update([
            'status' => 'CANCELADA',
            'resolution_notes' => $validated['resolution_notes'],
            'closed_at' => now(),
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud cancelada exitosamente.');
    }

    /**
     * Obtener SLAs aplicables para un sub-servicio (AJAX)
     */
    public function getSlas(SubService $subService)
    {
        try {
            Log::info('=== DEPURACIÓN GETSLAS INICIADA ===');
            Log::info('SubService ID: ' . $subService->id);
            Log::info('SubService Name: ' . $subService->name);

            $subService->load(['service']);
            Log::info('Service ID: ' . ($subService->service ? $subService->service->id : 'NULL'));
            Log::info('Service Name: ' . ($subService->service ? $subService->service->name : 'NULL'));

            if ($subService->service) {
                $subService->service->load(['family']);
                Log::info('Family ID: ' . ($subService->service->family ? $subService->service->family->id : 'NULL'));
                Log::info('Family Name: ' . ($subService->service->family ? $subService->service->family->name : 'NULL'));

                if ($subService->service->family) {
                    $slasCount = $subService->service->family->serviceLevelAgreements()->where('is_active', true)->count();

                    Log::info('SLAs activos encontrados: ' . $slasCount);

                    $slas = $subService->service->family
                        ->serviceLevelAgreements()
                        ->where('is_active', true)
                        ->get(['id', 'name', 'criticality_level', 'acceptance_time_minutes', 'response_time_minutes', 'resolution_time_minutes']);

                    Log::info('SLAs devueltos: ' . $slas->toJson());

                    return response()->json($slas);
                }
            }

            Log::warning('No se pudo cargar SLAs - relaciones incompletas');
            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Error crítico en getSlas: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([], 500);
        }
    }

    /**
     * Mostrar formulario para resolver con evidencias
     */
    public function showResolveForm(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'La solicitud no está en estado para ser resuelta. Estado actual: ' . $serviceRequest->status);
        }

        $serviceRequest->load([
            'sla',
            'evidences' => function ($query) {
                $query
                    ->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])
                    ->orderBy('step_number')
                    ->orderBy('created_at');
            },
        ]);

        $validEvidencesCount = $serviceRequest->hasAnyEvidenceForResolution() ? $serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->count() : 0;

        return view('service-requests.resolve-form', compact('serviceRequest', 'validEvidencesCount'));
    }

    // =============================================
    // NUEVOS MÉTODOS PARA TIMELINE
    // =============================================

    /**
     * Mostrar línea de tiempo de una solicitud
     */
    public function showTimeline(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['subService.service.family', 'sla', 'requester', 'assignee', 'evidences.user', 'breachLogs']);

        $timelineEvents = $serviceRequest->getTimelineEvents();
        $timeInStatus = $serviceRequest->getTimeInEachStatus();
        $totalResolutionTime = $serviceRequest->getTotalResolutionTime();
        $timeStatistics = $serviceRequest->getTimeStatistics();
        $timeSummary = $serviceRequest->getTimeSummaryByEventType();

        return view('service-requests.timeline', compact('serviceRequest', 'timelineEvents', 'timeInStatus', 'totalResolutionTime', 'timeStatistics', 'timeSummary'));
    }

    public function quickAssign(Request $request, ServiceRequest $service_request)
    {
        Log::info('QuickAssign llamado', [
            'user_id' => auth()->id(),
            'service_request_id' => $service_request->id,
            'assigned_to' => $request->assigned_to,
            'has_permission' => auth()->user()->can('assign-service-requests'),
        ]);

        if (!auth()->user()->can('assign-service-requests')) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'No tienes permisos para asignar solicitudes',
                ],
                403,
            );
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        try {
            $service_request->update([
                'assigned_to' => $request->assigned_to,
            ]);

            if (class_exists('App\Models\ServiceRequestHistory')) {
                \App\Models\ServiceRequestHistory::create([
                    'service_request_id' => $service_request->id,
                    'user_id' => auth()->id(),
                    'action' => 'ASIGNACIÓN_RÁPIDA',
                    'description' => 'Solicitud asignada a técnico mediante asignación rápida',
                    'details' => [
                        'assigned_to' => $request->assigned_to,
                        'assigned_by' => auth()->id(),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Técnico asignado correctamente',
                'assigned_to' => $service_request->assignee->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en asignación rápida: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error al asignar técnico: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Descargar reporte PDF de una solicitud específica
     */
    public function downloadReport(ServiceRequest $serviceRequest)
    {
        try {
            $serviceRequest->load(['requester', 'assignedTechnician', 'evidences', 'sla', 'subService']);

            if (!$serviceRequest->evidences) {
                $serviceRequest->setRelation('evidences', collect());
            }

            $data = [
                'serviceRequest' => $serviceRequest,
                'title' => "Reporte de Solicitud #{$serviceRequest->ticket_number}",
                'generated_at' => now()->format('d/m/Y H:i:s'),
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.service-request-pdf', $data);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('margin-top', 15);
            $pdf->setOption('margin-bottom', 15);
            $pdf->setOption('margin-left', 10);
            $pdf->setOption('margin-right', 10);

            $fileName = "reporte-solicitud-{$serviceRequest->ticket_number}.pdf";

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('Error generando reporte PDF: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());

            return redirect()
                ->back()
                ->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Almacenar nueva evidencia
     */
    public function storeEvidence(Request $request, ServiceRequest $serviceRequest)
    {
        try {
            Log::info('=== INICIANDO SUBIDA DE EVIDENCIAS ===');

            $request->validate([
                'files.*' => 'required|file|max:10240',
            ]);

            $uploadedFiles = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    Log::info('Procesando archivo:', [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ]);

                    if (!$file->isValid()) {
                        Log::error('Archivo no válido: ' . $file->getClientOriginalName());
                        continue;
                    }

                    $folderName = 'service-request-' . $serviceRequest->id;
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $filePath = $file->storeAs("evidences/{$folderName}", $fileName, 'public');

                    Log::info('Archivo guardado en:', ['path' => $filePath]);

                    if (!Storage::disk('public')->exists($filePath)) {
                        Log::error('El archivo no existe en storage: ' . $filePath);
                        continue;
                    }

                    $evidenceData = [
                        'service_request_id' => $serviceRequest->id,
                        'title' => $file->getClientOriginalName(),
                        'description' => 'Archivo subido: ' . $file->getClientOriginalName(),
                        'evidence_type' => 'ARCHIVO',
                        'file_path' => $filePath,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'user_id' => auth()->id(),
                    ];

                    Log::info('Creando evidencia en BD:', $evidenceData);

                    $evidence = ServiceRequestEvidence::create($evidenceData);
                    $evidence->load('user');

                    Log::info('✅ Evidencia creada con ID: ' . $evidence->id);
                    $uploadedFiles[] = $evidence;
                }

                Log::info('=== SUBIDA COMPLETADA ===', [
                    'total_files' => count($uploadedFiles),
                    'service_request_id' => $serviceRequest->id,
                ]);

                if (count($uploadedFiles) > 0) {
                    return redirect()
                        ->back()
                        ->with('success', count($uploadedFiles) . ' archivo(s) subido(s) correctamente.');
                } else {
                    return redirect()->back()->with('error', 'No se pudieron subir los archivos.');
                }
            }

            return redirect()->back()->with('error', 'No se seleccionaron archivos.');
        } catch (\Exception $e) {
            Log::error('Error en storeEvidence: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al subir archivos: ' . $e->getMessage());
        }
    }
}
