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


class ServiceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::with(['subService.service.family', 'sla', 'requester', 'assignee'])
            ->latest()
            ->paginate(10);

        return view('service-requests.index', compact('serviceRequests'));
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_service_id' => 'required|exists:sub_services,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'assigned_to' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
            'web_routes' => 'nullable|string',
            'main_web_route' => 'nullable|url',
        ]);

        // Generar número de ticket único
        $validated['ticket_number'] = 'SR-' . Str::upper(Str::random(8));
        $validated['requested_by'] = auth()->id();

        // Obtener el SLA seleccionado
        $sla = ServiceLevelAgreement::find($validated['sla_id']);

        // Calcular fechas límite
        $now = now();
        $validated['acceptance_deadline'] = $now->copy()->addMinutes($sla->acceptance_time_minutes);
        $validated['response_deadline'] = $now->copy()->addMinutes($sla->response_time_minutes);
        $validated['resolution_deadline'] = $now->copy()->addMinutes($sla->resolution_time_minutes);

        $serviceRequest = ServiceRequest::create($validated);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Solicitud de servicio creada exitosamente. Ticket: ' . $serviceRequest->ticket_number);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        // CARGAR TODAS LAS RELACIONES INCLUYENDO EVIDENCIAS
        $serviceRequest->load([
            'subService.service.family',
            'sla',
            'requester',
            'assignee',
            'breachLogs',
            'evidences', // Cargar todas las evidencias
            'stepByStepEvidences', // Cargar evidencias paso a paso
            'fileEvidences', // Cargar evidencias de archivo
            'commentEvidences' // Cargar evidencias de comentario
        ]);

        return view('service-requests.show', compact('serviceRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('error', 'Solo se pueden editar solicitudes en estado PENDIENTE.');
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
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('error', 'Solo se pueden editar solicitudes en estado PENDIENTE.');
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

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Solicitud de servicio actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        if (!in_array($serviceRequest->status, ['PENDIENTE', 'CANCELADA'])) {
            return redirect()->route('service-requests.index')
                ->with('error', 'Solo se pueden eliminar solicitudes en estado PENDIENTE o CANCELADA.');
        }

        $serviceRequest->delete();

        return redirect()->route('service-requests.index')
            ->with('success', 'Solicitud de servicio eliminada exitosamente.');
    }

    // =============================================
    // MÉTODOS PARA FLUJO DE TRABAJO CON EVIDENCIAS
    // =============================================

    /**
     * Aceptar una solicitud de servicio - ACTUALIZADO CON TIMELINE
     */
    public function accept(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()->back()->with('error', 'La solicitud ya ha sido procesada.');
        }

        $serviceRequest->update([
            'status' => 'ACEPTADA',
            'accepted_at' => now(), // TIMESTAMP PARA TIMELINE
            'assigned_to' => $serviceRequest->assigned_to ?? auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Solicitud aceptada exitosamente.');
    }

    /**
     * Marcar como en proceso - ACTUALIZADO CON TIMELINE
     */
    public function start(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'ACEPTADA') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado ACEPTADA.');
        }

        $serviceRequest->update([
            'status' => 'EN_PROCESO',
            'responded_at' => now(), // TIMESTAMP PARA TIMELINE
        ]);

        return redirect()->back()->with('success', 'Solicitud marcada como en proceso.');
    }

    /**
     * Mostrar formulario para resolver con evidencias
     */
    // En ServiceRequestController.php - VERIFICA ESTA LÍNEA EXACTA:
    /**
     * Mostrar formulario para resolver con evidencias
     */
    /**
     * Mostrar formulario para resolver con evidencias
     */
    public function showResolveForm(ServiceRequest $serviceRequest)
    {
        // Validar que la solicitud esté en estado EN_PROCESO
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('error', 'La solicitud no está en estado para ser resuelta. Estado actual: ' . $serviceRequest->status);
        }

        // Cargar las relaciones necesarias
        $serviceRequest->load([
            'sla',
            'evidences' => function ($query) {
                $query->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])
                    ->orderBy('step_number')
                    ->orderBy('created_at');
            }
        ]);

        // Usar el método que ya tienes para contar evidencias válidas
        $validEvidencesCount = $serviceRequest->hasAnyEvidenceForResolution()
            ? $serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->count()
            : 0;

        return view('service-requests.resolve-form', compact('serviceRequest', 'validEvidencesCount'));
    }

    /**
     * Resolver solicitud con validación de evidencias - ACTUALIZADO CON TIMELINE
     */
    public function resolveWithEvidence(Request $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()->back()
                ->with('error', 'La solicitud debe estar en estado EN PROCESO.');
        }

        // Validar que tenga al menos una evidencia paso a paso
        if (!$serviceRequest->stepByStepEvidences()->exists()) {
            return redirect()->back()
                ->with('error', 'Debe agregar al menos una evidencia paso a paso antes de resolver la solicitud.')
                ->withInput();
        }

        // Validar datos de resolución
        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:10',
            'actual_resolution_time' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($validated, $serviceRequest) {
                // Actualizar solicitud con timestamp de resolución
                $serviceRequest->update([
                    'status' => 'RESUELTA',
                    'resolution_notes' => $validated['resolution_notes'],
                    'actual_resolution_time' => $validated['actual_resolution_time'],
                    'resolved_at' => now(), // TIMESTAMP PARA TIMELINE
                ]);

                // Crear evidencia de sistema para la resolución
                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Resolución de Solicitud',
                    'description' => $validated['resolution_notes'],
                    'evidence_type' => ServiceRequestEvidence::TYPE_SYSTEM,
                    'evidence_data' => [
                        'action' => 'RESOLUTION',
                        'resolution_time' => $validated['actual_resolution_time'],
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now()->toISOString(),
                    ],
                ]);
            });

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', 'Solicitud resuelta correctamente con todas las evidencias.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al resolver la solicitud: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Resolver solicitud (método original - mantener compatibilidad) - ACTUALIZADO CON TIMELINE
     */
    /**
     * Resolver solicitud (método original - mantener compatibilidad) - ACTUALIZADO CON TIMELINE
     */
    /**
     * Resolver solicitud
     */
    public function resolve(Request $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('error', 'La solicitud no puede ser resuelta en su estado actual: ' . $serviceRequest->status);
        }

        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:10',
            'actual_resolution_time' => 'required|integer|min:1',
        ]);

        try {
            $serviceRequest->update([
                'resolution_notes' => $validated['resolution_notes'],
                'actual_resolution_time' => $validated['actual_resolution_time'],
                'resolved_at' => now(),
                'status' => 'RESUELTA' // ← EXACTAMENTE EN MAYÚSCULAS
            ]);

            // Verificar que se actualizó correctamente
            \Log::info("Solicitud {$serviceRequest->ticket_number} resuelta. Nuevo estado: {$serviceRequest->fresh()->status}");

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', 'Solicitud resuelta exitosamente.');
        } catch (\Exception $e) {
            \Log::error("Error al resolver solicitud: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al resolver la solicitud: ' . $e->getMessage())
                ->withInput();
        }
    }
    /**
     * Cerrar solicitud - ACTUALIZADO CON TIMELINE
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
            'closed_at' => now(), // TIMESTAMP PARA TIMELINE
            'satisfaction_score' => $validated['satisfaction_score'],
        ]);

        return redirect()->back()->with('success', 'Solicitud cerrada exitosamente.');
    }

    /**
     * Cancelar solicitud - ACTUALIZADO CON TIMELINE
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

    return redirect()->route('service-requests.show', $serviceRequest)
        ->with('success', 'Solicitud cancelada exitosamente.');
}

    /**
     * Obtener SLAs aplicables para un sub-servicio (AJAX)
     */
    public function getSlas(SubService $subService)
    {
        try {
            // Cargar las relaciones necesarias
            $subService->load(['service.family.serviceLevelAgreements']);

            // Verificar que existan las relaciones
            if (!$subService->service) {
                \Log::error("SubService {$subService->id} no tiene servicio asociado");
                return response()->json([]);
            }

            if (!$subService->service->family) {
                \Log::error("Service {$subService->service->id} no tiene familia asociada");
                return response()->json([]);
            }

            // Obtener SLAs activos de la familia
            $slas = $subService->service->family->serviceLevelAgreements()
                ->where('is_active', true)
                ->get(['id', 'name', 'criticality_level', 'acceptance_time_minutes', 'response_time_minutes', 'resolution_time_minutes']);

            \Log::info("Found {$slas->count()} SLAs for sub-service {$subService->id}");

            return response()->json($slas);
        } catch (\Exception $e) {
            \Log::error('Error loading SLAs for sub-service ' . $subService->id . ': ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Pausar una solicitud - ACTUALIZADO CON TIMELINE
     */
    public function pause(ServiceRequest $serviceRequest, Request $request)
    {
        if (!in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO'])) {
            return redirect()->back()->with('error', 'Solo se pueden pausar solicitudes en estado ACEPTADA o EN PROCESO.');
        }

        $validated = $request->validate([
            'pause_reason' => 'required|string|max:500',
        ]);

        // Usar el método del modelo que ya incluye los timestamps
        $serviceRequest->pause($validated['pause_reason']);

        return redirect()->back()->with('success', 'Solicitud pausada exitosamente.');
    }

    /**
     * Reanudar una solicitud - ACTUALIZADO CON TIMELINE
     */
    public function resume(ServiceRequest $serviceRequest)
    {
        if (!$serviceRequest->isPaused()) {
            return redirect()->back()->with('error', 'La solicitud no está pausada.');
        }

        // Usar el método del modelo que ya incluye los timestamps
        $serviceRequest->resume();

        return redirect()->back()->with('success', 'Solicitud reanudada exitosamente.');
    }

    // =============================================
    // NUEVOS MÉTODOS PARA TIMELINE
    // =============================================

    /**
     * Mostrar línea de tiempo de una solicitud
     */
    public function showTimeline(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load([
            'subService.service.family',
            'sla',
            'requester',
            'assignee',
            'evidences.user',
            'breachLogs'
        ]);

        $timelineEvents = $serviceRequest->getTimelineEvents();
        $timeInStatus = $serviceRequest->getTimeInEachStatus();
        $totalResolutionTime = $serviceRequest->getTotalResolutionTime();
        $timeStatistics = $serviceRequest->getTimeStatistics();
        $timeSummary = $serviceRequest->getTimeSummaryByEventType();

        return view('service-requests.timeline', compact(
            'serviceRequest',
            'timelineEvents',
            'timeInStatus',
            'totalResolutionTime',
            'timeStatistics',
            'timeSummary'
        ));
    }
}
