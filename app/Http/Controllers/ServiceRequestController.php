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
            'auto_assign' => 'nullable|boolean', // Agregar validación para auto_assign
        ]);

        // Generar número de ticket
        $validated['ticket_number'] = ServiceRequest::generateProfessionalTicketNumber($validated['sub_service_id'], $validated['criticality_level']);

        // LÓGICA DE AUTO-ASIGNACIÓN
        if ($request->has('auto_assign') && $request->boolean('auto_assign')) {
            // Si auto_assign está marcado, asignar al usuario autenticado
            $validated['assigned_to'] = auth()->id();
        } else {
            // Si no está marcado, usar el assigned_to del formulario
            // (ya validado que existe si se proporciona)
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
        // CARGAR RELACIONES BÁSICAS (las que existen como métodos de relación)
        $serviceRequest->load([
            'subService.service.family',
            'sla',
            'requester',
            'assignee',
            'breachLogs',
            'evidences.user', // ✅ Esta es la única relación real que existe
        ]);

        // LOS ACCESSORS SE CARGAN AUTOMÁTICAMENTE:
        // - stepByStepEvidences (accessor)
        // - fileEvidences (accessor)
        // - commentEvidences NO EXISTE en tu modelo actual
        // - systemEvidences NO EXISTE en tu modelo actual

        return view('service-requests.show', compact('serviceRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceRequest $serviceRequest)
    {
        // Estados que permiten edición
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
        // Estados que permiten edición (misma lógica que en edit)
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
     * Aceptar una solicitud de servicio - SOLO CAMBIA ESTADO
     */
    public function accept(ServiceRequest $serviceRequest)
    {
        // Verificar que la solicitud esté pendiente
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()
                ->back()
                ->with('error', 'Esta solicitud ya no puede ser aceptada. Estado actual: ' . $serviceRequest->status);
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                // ✅ SOLO CAMBIAR ESTADO, NO MODIFICAR assigned_to
                $serviceRequest->update([
                    'status' => 'ACEPTADA',
                    'accepted_at' => now(),
                    // assigned_to se mantiene como estaba (puede ser null)
                ]);

                // Crear evidencia de sistema para el timeline
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
     * Iniciar procesamiento de solicitud - Cambiar a EN_PROCESO
     */
    public function start(ServiceRequest $serviceRequest)
    {
        // Verificar que la solicitud esté aceptada
        if ($serviceRequest->status !== 'ACEPTADA') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado ACEPTADA para iniciar el procesamiento.');
        }

        // ✅ SOLO AQUÍ validar que tenga técnico asignado
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
     * Mostrar formulario para resolver con evidencias
     */
    public function showResolveForm(ServiceRequest $serviceRequest)
    {
        // Validar que la solicitud esté en estado EN_PROCESO
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'La solicitud no está en estado para ser resuelta. Estado actual: ' . $serviceRequest->status);
        }

        // Cargar las relaciones necesarias
        $serviceRequest->load([
            'sla',
            'evidences' => function ($query) {
                $query
                    ->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])
                    ->orderBy('step_number')
                    ->orderBy('created_at');
            },
        ]);

        // Usar el método que ya tienes para contar evidencias válidas
        $validEvidencesCount = $serviceRequest->hasAnyEvidenceForResolution() ? $serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->count() : 0;

        return view('service-requests.resolve-form', compact('serviceRequest', 'validEvidencesCount'));
    }

    /**
     * Resolver solicitud - Cambiar a RESUELTA
     */
    public function resolve(Request $request, ServiceRequest $serviceRequest)
    {
        // Verificar que la solicitud esté en proceso
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado EN PROCESO para ser resuelta.');
        }

        \Log::info('Iniciando resolución de solicitud', [
            'ticket' => $serviceRequest->ticket_number,
            'user' => auth()->id(),
            'data' => $request->all(),
        ]);

        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:10|max:1000',
            'actual_resolution_time' => 'required|integer|min:1',
        ]);

        \Log::info('Datos validados', $validated);

        try {
            DB::transaction(function () use ($validated, $serviceRequest) {
                // Actualizar el estado a RESUELTA
                $serviceRequest->update([
                    'status' => 'RESUELTA',
                    'resolution_notes' => $validated['resolution_notes'],
                    'actual_resolution_time' => $validated['actual_resolution_time'],
                    'resolved_at' => now(),
                ]);

                \Log::info('Solicitud actualizada', [
                    'new_status' => 'RESUELTA',
                    'resolution_time' => $validated['actual_resolution_time'],
                ]);

                // Crear evidencia de sistema para el timeline
                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Solicitud Resuelta',
                    'description' => $validated['resolution_notes'],
                    'evidence_type' => 'SISTEMA',
                    'created_by' => auth()->id(),
                    'evidence_data' => [
                        'action' => 'RESOLVED',
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now()->toISOString(),
                        'resolution_time' => $validated['actual_resolution_time'],
                        'previous_status' => 'EN_PROCESO',
                        'new_status' => 'RESUELTA',
                    ],
                ]);

                \Log::info('Evidencia de sistema creada');
            });

            \Log::info('Resolución completada exitosamente');

            return redirect()->back()->with('success', 'Solicitud resuelta correctamente. Esperando confirmación del cliente.');
        } catch (\Exception $e) {
            \Log::error('Error al resolver la solicitud: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()
                ->back()
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

        return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud cancelada exitosamente.');
    }

    /**
     * Obtener SLAs aplicables para un sub-servicio (AJAX)
     */
    public function getSlas(SubService $subService)
    {
        try {
            \Log::info('=== DEPURACIÓN GETSLAS INICIADA ===');
            \Log::info('SubService ID: ' . $subService->id);
            \Log::info('SubService Name: ' . $subService->name);

            // Cargar relaciones paso a paso para depurar
            $subService->load(['service']);
            \Log::info('Service ID: ' . ($subService->service ? $subService->service->id : 'NULL'));
            \Log::info('Service Name: ' . ($subService->service ? $subService->service->name : 'NULL'));

            if ($subService->service) {
                $subService->service->load(['family']);
                \Log::info('Family ID: ' . ($subService->service->family ? $subService->service->family->id : 'NULL'));
                \Log::info('Family Name: ' . ($subService->service->family ? $subService->service->family->name : 'NULL'));

                if ($subService->service->family) {
                    // Verificar SLAs directamente
                    $slasCount = $subService->service->family->serviceLevelAgreements()->where('is_active', true)->count();

                    \Log::info('SLAs activos encontrados: ' . $slasCount);

                    $slas = $subService->service->family
                        ->serviceLevelAgreements()
                        ->where('is_active', true)
                        ->get(['id', 'name', 'criticality_level', 'acceptance_time_minutes', 'response_time_minutes', 'resolution_time_minutes']);

                    \Log::info('SLAs devueltos: ' . $slas->toJson());

                    return response()->json($slas);
                }
            }

            \Log::warning('No se pudo cargar SLAs - relaciones incompletas');
            return response()->json([]);
        } catch (\Exception $e) {
            \Log::error('Error crítico en getSlas: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
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
        \Log::info('QuickAssign llamado', [
            'user_id' => auth()->id(),
            'service_request_id' => $service_request->id,
            'assigned_to' => $request->assigned_to,
            'has_permission' => auth()->user()->can('assign-service-requests'),
        ]);

        // Verificar permisos
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
            // Actualizar la asignación
            $service_request->update([
                'assigned_to' => $request->assigned_to,
            ]);

            // Opcional: Registrar en el historial
            if (class_exists('App\Models\ServiceRequestHistory')) {
                ServiceRequestHistory::create([
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
            \Log::error('Error en asignación rápida: ' . $e->getMessage());

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
        // Cargar relaciones básicas con manejo seguro
        $serviceRequest->load([
            'requester',
            'assignedTechnician',
            'evidences', // Asegurar que se cargue la relación
            'sla',
            'subService'
        ]);

        // Verificar y asegurar que evidences no sea null
        if (!$serviceRequest->evidences) {
            $serviceRequest->setRelation('evidences', collect());
        }

        $data = [
            'serviceRequest' => $serviceRequest,
            'title' => "Reporte de Solicitud #{$serviceRequest->ticket_number}",
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        // Generar PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.service-request-pdf', $data);

        // Configurar el PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 15);
        $pdf->setOption('margin-bottom', 15);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        $fileName = "reporte-solicitud-{$serviceRequest->ticket_number}.pdf";

        // Descargar el PDF
        return $pdf->download($fileName);

    } catch (\Exception $e) {
        \Log::error('Error generando reporte PDF: ' . $e->getMessage());
        \Log::error('File: ' . $e->getFile());
        \Log::error('Line: ' . $e->getLine());

        return redirect()->back()
            ->with('error', 'Error al generar el reporte: ' . $e->getMessage());
    }
}

}
