<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveServiceRequestRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Requests\UpdateServiceRequestRequest;
use App\Http\Requests\RejectServiceRequestRequest;
use App\Http\Requests\PauseServiceRequestRequest;
use App\Http\Requests\UploadEvidenceRequest;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequestEvidence;
use App\Services\ServiceRequestService;
use App\Services\ServiceRequestWorkflowService;
use App\Services\EvidenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ServiceRequestController extends Controller
{
    protected ServiceRequestService $serviceRequestService;
    protected ServiceRequestWorkflowService $workflowService;
    protected EvidenceService $evidenceService;

    public function __construct(
        ServiceRequestService $serviceRequestService,
        ServiceRequestWorkflowService $workflowService,
        EvidenceService $evidenceService
    ) {
        $this->serviceRequestService = $serviceRequestService;
        $this->workflowService = $workflowService;
        $this->evidenceService = $evidenceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Preparar filtros
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'criticality' => $request->get('criticality'),
            'requester' => $request->get('requester'), // nombre o email parcial
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        // Validación ligera de fechas (formato YYYY-MM-DD)
        foreach (['start_date','end_date'] as $key) {
            if (!empty($filters[$key]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) {
                $filters[$key] = null; // descartar si formato inválido
            }
        }
        // Si ambas presentes y rango invertido, intercambiar
        if (!empty($filters['start_date']) && !empty($filters['end_date']) && $filters['start_date'] > $filters['end_date']) {
            [$filters['start_date'], $filters['end_date']] = [$filters['end_date'], $filters['start_date']];
        }

        // Obtener datos usando el service
        $serviceRequests = $this->serviceRequestService->getFilteredServiceRequests($filters, 15);
        $stats = $this->serviceRequestService->getDashboardStats();

        $data = array_merge(
            compact('serviceRequests'),
            $stats
        );

        // Si es petición AJAX, devolver solo el contenido parcial
        if ($request->ajax() || $request->wantsJson()) {
            return view('service-requests.partials.table-content', $data);
        }

        return view('service-requests.index', $data);
    }

    /**
     * Sugerencias de solicitantes para autocompletar.
     */
    public function suggestRequesters(Request $request)
    {
        $term = trim($request->get('term',''));
        if ($term === '') {
            return response()->json([]);
        }
        $query = \App\Models\Requester::active()
            ->select(['id','name','email'])
            ->where(function($q) use ($term) {
                $q->where('name','LIKE',"%{$term}%")
                  ->orWhere('email','LIKE',"%{$term}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
        $results = $query->map(fn($r)=>[
            'id' => $r->id,
            'name' => $r->name,
            'email' => $r->email,
            'display' => $r->name . ($r->email ? " ({$r->email})" : '')
        ]);
        return response()->json($results);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = $this->serviceRequestService->getCreateFormData();

        return view('service-requests.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequestRequest $request)
    {
        try {
            $serviceRequest = $this->serviceRequestService->createServiceRequest($request->validated());

            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('success', "Solicitud creada exitosamente! Ticket: {$serviceRequest->ticket_number}");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al crear la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest = $this->serviceRequestService->loadServiceRequestForShow($serviceRequest);

        // Obtener todos los usuarios como técnicos potenciales
        $technicians = User::orderBy('name')->get();

        return view('service-requests.show', compact('serviceRequest', 'technicians'));
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

        $data = $this->serviceRequestService->getEditFormData();

        return view('service-requests.edit', compact('serviceRequest') + $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest)
    {
        $editableStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];

        if (!in_array($serviceRequest->status, $editableStatuses)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'No se pueden editar solicitudes en estado: ' . $serviceRequest->status);
        }

        try {
            $this->serviceRequestService->updateServiceRequest($serviceRequest, $request->validated());

            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('success', 'Solicitud de servicio actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        if (!in_array($serviceRequest->status, ['PENDIENTE', 'CANCELADA'])) {
            return redirect()
                ->route('service-requests.index')
                ->with('error', 'Solo se pueden eliminar solicitudes en estado PENDIENTE o CANCELADA.');
        }

        try {
            $this->serviceRequestService->deleteServiceRequest($serviceRequest);

            return redirect()
                ->route('service-requests.index')
                ->with('success', 'Solicitud de servicio eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->route('service-requests.index')
                ->with('error', 'Error al eliminar la solicitud: ' . $e->getMessage());
        }
    }

    // =============================================
    // MÉTODOS PARA FLUJO DE TRABAJO CON EVIDENCIAS
    // =============================================

    /**
     * Aceptar una solicitud de servicio
     */
    public function accept(ServiceRequest $serviceRequest)
    {
        $result = $this->workflowService->acceptRequest($serviceRequest);

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    /**
     * Rechazar una solicitud de servicio
     */
    public function reject(RejectServiceRequestRequest $request, ServiceRequest $serviceRequest)
    {
        $result = $this->workflowService->rejectRequest(
            $serviceRequest,
            $request->validated()['rejection_reason']
        );

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function start(Request $request, ServiceRequest $serviceRequest)
    {
        $useStandardTasks = $request->input('use_standard_tasks', '0') === '1';
        $result = $this->workflowService->startProcessing($serviceRequest, $useStandardTasks);

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function resolve(Request $request, ServiceRequest $serviceRequest)
    {
        $data = [
            'resolution_notes' => $request->input('resolution_notes', 'Resolución completada'),
            'actual_resolution_time' => $request->input('actual_resolution_time', 60),
        ];

        $result = $this->workflowService->resolveRequest($serviceRequest, $data);

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Mostrar formulario para reasignar técnico
     */
    public function reassign(ServiceRequest $service_request)
    {
        $allowedStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];
        if (!in_array($service_request->status, $allowedStatuses)) {
            return redirect()
                ->route('service-requests.show', $service_request)
                ->with('error', 'No se puede reasignar una solicitud en estado: ' . $service_request->status);
        }

        // Todos los usuarios son técnicos - excluir solo el actual
        $technicians = User::where('id', '!=', $service_request->assigned_to)->orderBy('name')->get();

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

    public function pause(ServiceRequest $serviceRequest, PauseServiceRequestRequest $request)
    {
        $result = $this->workflowService->pauseRequest(
            $serviceRequest,
            $request->validated()['pause_reason']
        );

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function resume(ServiceRequest $serviceRequest)
    {
        $result = $this->workflowService->resumeRequest($serviceRequest);

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function close(Request $request, ServiceRequest $serviceRequest)
    {
        \Log::info('Intento de cierre', [
            'service_request_id' => $serviceRequest->id,
            'current_status' => $serviceRequest->status,
            'user_id' => auth()->id(),
            'closure_type' => $request->has('closure_type') ? $request->closure_type : 'desconocido',
        ]);

        // Determinar el tipo de cierre basado en el estado actual
        $isVencimiento = $serviceRequest->status === 'PAUSADA';
        $isCierreNormal = $serviceRequest->status === 'RESUELTA';

        // Validar estados permitidos
        if (!$isVencimiento && !$isCierreNormal) {
            return redirect()->route('service-requests.show', $serviceRequest->id)->with('error', 'Solo se pueden cerrar solicitudes RESUELTAS o PAUSADAS por vencimiento.');
        }

        // Validaciones diferentes según el tipo de cierre
        if ($isVencimiento) {
            // Cierre por vencimiento requiere motivo
            $request->validate([
                'closure_reason' => 'required|string|min:10',
            ]);
        } else {
            // Cierre normal puede tener descripción opcional
            $request->validate([
                'resolution_description' => 'sometimes|string|min:0',
            ]);
        }

        try {
            \DB::beginTransaction();

            $updateData = [
                'status' => 'CERRADA',
                'closed_at' => now(),
                'updated_at' => now(),
            ];

            // Construir notas según el tipo de cierre
            $currentNotes = $serviceRequest->resolution_notes ?? '';

            if ($isVencimiento) {
                $closureDetails = "\n\n=== CIERRE POR VENCIMIENTO ===\n" . 'Fecha/Hora: ' . now()->format('d/m/Y H:i:s') . "\n" . 'Usuario: ID ' . auth()->id() . "\n" . 'Motivo: ' . $request->closure_reason;
            } else {
                $closureDetails = "\n\n=== CIERRE NORMAL ===\n" . 'Fecha/Hora: ' . now()->format('d/m/Y H:i:s') . "\n" . 'Usuario: ID ' . auth()->id();

                if ($request->resolution_description) {
                    $closureDetails .= "\nDescripción: " . $request->resolution_description;
                }
            }

            $updateData['resolution_notes'] = trim($currentNotes . $closureDetails);

            // Actualizar la solicitud
            \DB::table('service_requests')->where('id', $serviceRequest->id)->update($updateData);

            // Registrar en el historial
            if (class_exists('App\Models\ServiceRequestHistory')) {
                $actionType = $isVencimiento ? 'CIERRE_POR_VENCIMIENTO' : 'CIERRE_NORMAL';
                $description = $isVencimiento ? 'Solicitud cerrada por vencimiento del plazo - ' . $request->closure_reason : 'Solicitud cerrada normalmente' . ($request->resolution_description ? ' - ' . $request->resolution_description : '');

                \App\Models\ServiceRequestHistory::create([
                    'service_request_id' => $serviceRequest->id,
                    'user_id' => auth()->id(),
                    'action' => $actionType,
                    'description' => $description,
                    'details' => json_encode(
                        [
                            'closure_reason' => $request->closure_reason ?? null,
                            'resolution_description' => $request->resolution_description ?? null,
                            'previous_status' => $serviceRequest->getOriginal('status'),
                            'closed_by' => auth()->id(),
                            'closed_at' => now()->toISOString(),
                            'closure_type' => $isVencimiento ? 'vencimiento' : 'normal',
                        ],
                        JSON_UNESCAPED_UNICODE,
                    ),
                ]);
            }

            \DB::commit();
            $serviceRequest->refresh();

            \Log::info('Solicitud cerrada exitosamente', [
                'service_request_id' => $serviceRequest->id,
                'new_status' => $serviceRequest->status,
                'closure_type' => $isVencimiento ? 'vencimiento' : 'normal',
            ]);

            $message = $isVencimiento ? 'Solicitud cerrada correctamente por vencimiento' : 'Solicitud cerrada correctamente';

            return redirect()->route('service-requests.show', $serviceRequest->id)->with('success', $message);
        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error al cerrar solicitud: ' . $e->getMessage(), [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('service-requests.show', $serviceRequest->id)
                ->with('error', 'Error al cerrar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Reabrir solicitud (VERSIÓN CORREGIDA)
     */
    public function reopen(Request $request, ServiceRequest $serviceRequest)
    {
        \Log::info('=== 🔄 REOPEN METHOD ===');

        $allowedStatuses = ['RESUELTA', 'CERRADA'];

        if (!in_array($serviceRequest->status, $allowedStatuses)) {
            return redirect()->back()->with('error', 'La solicitud no puede ser reabierta desde el estado actual.');
        }

        try {
            ServiceRequest::withoutEvents(function () use ($serviceRequest) {
                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'reopened_at' => now(),
                    // Limpiar campos de finalización
                    'resolved_at' => null,
                    'closed_at' => null,
                    'resolution_notes' => null, // Opcional: limpiar notas anteriores
                ]);
            });

            \Log::info('🎉 ÉXITO: Solicitud reabierta exitosamente');

            // Crear evidencia
            ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'Solicitud Reabierta',
                'description' => 'La solicitud ha sido reabierta para trabajo adicional.',
                'evidence_type' => 'SISTEMA',
                'created_by' => auth()->id(),
                'evidence_data' => [
                    'action' => 'REOPENED',
                    'reopened_by' => auth()->id(),
                    'reopened_at' => now()->toISOString(),
                    'previous_status' => $serviceRequest->status,
                    'new_status' => 'EN_PROCESO',
                ],
            ]);

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', '¡Solicitud reabierta correctamente!');
        } catch (\Exception $e) {
            \Log::error('❌ ERROR al reabrir: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al reabrir la solicitud: ' . $e->getMessage());
        }
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

    public function quickAssignRequester(Request $request, ServiceRequest $service_request)
    {
        Log::info('QuickAssignRequester llamado', [
            'user_id' => auth()->id(),
            'service_request_id' => $service_request->id,
            'requester_id' => $request->requester_id,
            'has_permission' => auth()->user()->can('assign-service-requests'),
        ]);

        if (!auth()->user()->can('assign-service-requests')) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'No tienes permisos para asignar solicitantes',
                ],
                403,
            );
        }

        $validated = $request->validate([
            'requester_id' => 'required|exists:requesters,id',
        ]);

        try {
            $previousRequester = $service_request->requester_id;

            $service_request->update([
                'requester_id' => $validated['requester_id'],
            ]);

            $service_request->load('requester');

            if (class_exists('App\Models\ServiceRequestHistory')) {
                \App\Models\ServiceRequestHistory::create([
                    'service_request_id' => $service_request->id,
                    'user_id' => auth()->id(),
                    'action' => 'ASIGNACION_SOLICITANTE_RAPIDA',
                    'description' => 'Solicitud reasignada a un nuevo solicitante mediante asignación rápida',
                    'details' => [
                        'previous_requester' => $previousRequester,
                        'new_requester' => $validated['requester_id'],
                        'assigned_by' => auth()->id(),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Solicitante asignado correctamente',
                'requester_name' => $service_request->requester->name ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en asignación rápida de solicitante: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error al asignar solicitante: ' . $e->getMessage(),
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
            $serviceRequest->load(['requester', 'assignee', 'evidences', 'sla', 'subService']);

            if (!$serviceRequest->evidences) {
                $serviceRequest->setRelation('evidences', collect());
            }

            // Procesar evidencias usando el service
            $evidencesWithBase64 = $this->evidenceService->prepareEvidencesForPdf($serviceRequest);
            $serviceRequest->setRelation('evidences', $evidencesWithBase64);

            // Log para debugging
            $totalEvidences = $serviceRequest->evidences->count();
            $fileEvidences = $serviceRequest->evidences->where('file_path', '!=', null)->count();
            $imageEvidences = $serviceRequest->evidences->where('is_image', true)->count();
            $foundEvidences = $serviceRequest->evidences->where('file_found', true)->count();

            Log::info("PDF Generation - Total: {$totalEvidences}, Con archivos: {$fileEvidences}, Imágenes: {$imageEvidences}, Encontrados: {$foundEvidences}");

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
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);

            $fileName = "reporte-solicitud-{$serviceRequest->ticket_number}.pdf";

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('Error generando reporte PDF: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());

            return redirect()
                ->back()
                ->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }
    /**
     * Almacenar nueva evidencia
     */
    public function storeEvidence(UploadEvidenceRequest $request, ServiceRequest $serviceRequest)
    {
        try {
            $result = $this->evidenceService->uploadEvidences(
                $serviceRequest,
                $request->file('files')
            );

            if ($result['success_count'] > 0) {
                $message = $result['success_count'] . ' archivo(s) subido(s) correctamente.';

                if ($result['error_count'] > 0) {
                    $message .= ' ' . $result['error_count'] . ' archivo(s) con errores.';
                }

                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'No se pudieron subir los archivos.');
            }
        } catch (\Exception $e) {
            Log::error('Error en storeEvidence: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al subir archivos: ' . $e->getMessage());
        }
    }
}
