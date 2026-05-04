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
use App\Models\SavedFilter;
use App\Models\Technician;
use App\Services\ServiceRequestService;
use App\Services\ServiceRequestWorkflowService;
use App\Services\EvidenceService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $globalSearch = trim((string) $request->get('q', $request->get('search', '')));

        // Preparar filtros
        $sortBy = (string) $request->get('sort_by', 'recent');
        $allowedSorts = ['recent', 'oldest', 'priority_high', 'priority_low', 'status_az', 'status_za', 'due_date'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'recent';
        }

        $filters = [
            'search' => $globalSearch,
            'status' => $request->get('status'),
            'criticality' => $request->get('criticality'),
            'due_status' => $request->get('due_status'),
            'requester' => $request->get('requester'), // nombre o email parcial
            'service_id' => $request->get('service_id'),
            'company_id' => $request->get('company_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'open' => $request->boolean('open'),
            'exclude_closed' => $request->boolean('exclude_closed'),
            'in_course' => $request->boolean('in_course'),
            'in_process' => $request->boolean('in_process'),
            'sort_by' => $sortBy,
        ];

        if (!in_array($filters['due_status'], ['with_due', 'without_due', 'overdue', 'due_soon'], true)) {
            $filters['due_status'] = null;
        }

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
        // Estadísticas ajustadas a los mismos filtros de la tabla
        $stats = $this->serviceRequestService->getFilteredStats($filters);

        // Servicios para filtro avanzado
        $currentCompanyId = (int) session('current_company_id');
        $services = Service::active()
            ->ordered()
            ->with('family:id,name')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->get(['id', 'name', 'service_family_id']);

        $savedFilters = SavedFilter::query()
            ->where('user_id', Auth::id())
            ->where('context', 'service-requests.index')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->where(function ($q) use ($currentCompanyId) {
                    $q->whereNull('company_id')
                      ->orWhere('company_id', $currentCompanyId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'filters']);

        $openStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'REABIERTO'];
        $slaAlerts = [
            'overdue' => ServiceRequest::query()
                ->whereIn('status', $openStatuses)
                ->whereNotNull('resolution_deadline')
                ->where('resolution_deadline', '<', now())
                ->count(),
            'dueSoon' => ServiceRequest::query()
                ->whereIn('status', $openStatuses)
                ->whereNotNull('resolution_deadline')
                ->whereBetween('resolution_deadline', [now(), now()->addHours(24)])
                ->count(),
        ];

        $dueAlerts = [
            'overdue' => ServiceRequest::query()
                ->whereIn('status', $openStatuses)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'dueSoon' => ServiceRequest::query()
                ->whereIn('status', $openStatuses)
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
                ->count(),
        ];

        $inCourseCount = ServiceRequest::query()
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->whereNotNull('accepted_at')
            ->where('status', 'ACEPTADA')
            ->count();

        $inProcessCount = ServiceRequest::query()
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->where('status', 'EN_PROCESO')
            ->count();

        $data = array_merge(
            compact('serviceRequests', 'services', 'savedFilters', 'slaAlerts', 'dueAlerts', 'inCourseCount', 'inProcessCount'),
            $stats
        );

        // Si es petición AJAX, devolver solo el contenido parcial
        if ($request->ajax() || $request->wantsJson()) {
            return view('service-requests.partials.table-content', $data);
        }

        return view('service-requests.index', $data);
    }

    public function savedFiltersIndex(Request $request)
    {
        $currentCompanyId = (int) $request->session()->get('current_company_id');

        $presets = SavedFilter::query()
            ->where('user_id', Auth::id())
            ->where('context', 'service-requests.index')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->where(function ($q) use ($currentCompanyId) {
                    $q->whereNull('company_id')
                      ->orWhere('company_id', $currentCompanyId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'filters', 'updated_at']);

        return response()->json($presets);
    }

    public function savedFiltersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'filters' => 'required|array',
            'filters.search' => 'nullable|string|max:255',
            'filters.status' => 'nullable|string|max:40',
            'filters.criticality' => 'nullable|string|max:40',
            'filters.due_status' => 'nullable|string|max:40',
            'filters.service_id' => 'nullable',
            'filters.requester' => 'nullable|string|max:255',
            'filters.start_date' => 'nullable|date',
            'filters.end_date' => 'nullable|date',
            'filters.open' => 'nullable',
            'filters.exclude_closed' => 'nullable',
            'filters.in_course' => 'nullable',
            'filters.in_process' => 'nullable',
            'filters.sort_by' => 'nullable|string|max:40',
        ]);

        $allowedKeys = ['search', 'status', 'criticality', 'due_status', 'service_id', 'requester', 'start_date', 'end_date', 'open', 'exclude_closed', 'in_course', 'in_process', 'sort_by'];
        $filters = collect($validated['filters'])
            ->only($allowedKeys)
            ->map(function ($value) {
                if (is_string($value)) {
                    return trim($value);
                }
                return $value;
            })
            ->filter(function ($value) {
                return !($value === null || $value === '' || $value === false);
            })
            ->toArray();

        if (empty($filters)) {
            return response()->json([
                'message' => 'No hay filtros válidos para guardar.',
            ], 422);
        }

        $currentCompanyId = (int) $request->session()->get('current_company_id');

        $savedFilter = SavedFilter::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'company_id' => $currentCompanyId ?: null,
                'context' => 'service-requests.index',
                'name' => $validated['name'],
            ],
            [
                'filters' => $filters,
            ],
        );

        return response()->json([
            'message' => 'Filtro guardado correctamente.',
            'preset' => $savedFilter->only(['id', 'name', 'filters', 'updated_at']),
        ]);
    }

    public function savedFiltersDestroy(SavedFilter $savedFilter, Request $request)
    {
        $currentCompanyId = (int) $request->session()->get('current_company_id');

        if ((int) $savedFilter->user_id !== (int) Auth::id() || $savedFilter->context !== 'service-requests.index') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($savedFilter->company_id !== null && (int) $savedFilter->company_id !== $currentCompanyId) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $savedFilter->delete();

        return response()->json(['message' => 'Filtro eliminado correctamente.']);
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
        $currentCompanyId = $request->session()->get('current_company_id');
        $query = \App\Models\Requester::active()
            ->select(['id','name','email'])
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
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
    public function create(Request $request)
    {
        $selectedSubServiceId = $request->old('sub_service_id');
        $selectedSubServiceId = $selectedSubServiceId ? (int) $selectedSubServiceId : null;

        $data = $this->serviceRequestService->getCreateFormData($selectedSubServiceId);

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
            Log::error('Error al crear la solicitud (controller store): ' . $e->getMessage(), [
                'exception' => $e,
            ]);
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

        $technicians = User::query()
            ->with(['technician.companies' => function ($query) use ($serviceRequest) {
                $query->where('companies.id', $serviceRequest->company_id)->select('companies.id', 'name');
            }])
            ->whereHas('technician', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('technician.companies', function ($query) use ($serviceRequest) {
                $query->where('companies.id', $serviceRequest->company_id);
            })
            ->orderBy('name')
            ->get();

        return view('service-requests.show', compact('serviceRequest', 'technicians'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ServiceRequest $serviceRequest)
    {
        $editableStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'CERRADA'];

        if (!in_array($serviceRequest->status, $editableStatuses)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'No se pueden editar solicitudes en estado: ' . $serviceRequest->status);
        }

        $selectedSubServiceId = $request->old('sub_service_id');
        $selectedSubServiceId = $selectedSubServiceId ? (int) $selectedSubServiceId : (int) $serviceRequest->sub_service_id;

        $data = $this->serviceRequestService->getEditFormData($selectedSubServiceId);

        return view('service-requests.edit', compact('serviceRequest') + $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest)
    {
        $editableStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'CERRADA'];

        if (!in_array($serviceRequest->status, $editableStatuses)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'No se pueden editar solicitudes en estado: ' . $serviceRequest->status);
        }

        try {
            $updatedRequest = $this->serviceRequestService->updateServiceRequest($serviceRequest, $request->validated())
                ->fresh(['subService']);
            $subServiceName = $updatedRequest?->subService?->name;
            $successMessage = 'Solicitud de servicio actualizada exitosamente.';
            if ($subServiceName) {
                $successMessage .= ' Subservicio: ' . $subServiceName . '.';
            }

            return redirect()
                ->route('service-requests.show', [
                    'service_request' => $serviceRequest,
                    'updated' => 1,
                ])
                ->with('success', $successMessage);
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
    public function accept(Request $request, ServiceRequest $serviceRequest)
    {
        $result = $this->workflowService->acceptRequest($serviceRequest);
        $acceptAndStart = $request->boolean('accept_and_start');
        $focusTasks = $request->boolean('focus_tasks');

        if (($result['success'] ?? false) && $acceptAndStart) {
            $startResult = $this->workflowService->startProcessing($serviceRequest, false);

            if (!($startResult['success'] ?? false)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Solicitud aceptada, pero no se pudo iniciar el servicio: ' . ($startResult['message'] ?? 'Error desconocido.'),
                    ], 422);
                }

                return redirect()
                    ->to(route('service-requests.show', $serviceRequest) . ($focusTasks ? '#tasks-panel-' . $serviceRequest->id : ''))
                    ->with('error', 'Solicitud aceptada, pero no se pudo iniciar el servicio: ' . ($startResult['message'] ?? 'Error desconocido.'));
            }

            $result = [
                'success' => true,
                'message' => 'Solicitud aceptada e iniciada correctamente.',
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        if ($focusTasks) {
            return redirect()
                ->to(route('service-requests.show', $serviceRequest) . '#tasks-panel-' . $serviceRequest->id)
                ->with($result['success'] ? 'success' : 'error', $result['message']);
        }

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
        $focusTasks = $request->boolean('focus_tasks');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        if ($focusTasks) {
            return redirect()
                ->to(route('service-requests.show', $serviceRequest) . '#tasks-panel-' . $serviceRequest->id)
                ->with($result['success'] ? 'success' : 'error', $result['message']);
        }

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function resolve(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'resolution_description' => 'required|string|min:10|max:8000',
            'resolution_notes' => 'nullable|string|max:8000',
            'actual_resolution_time' => 'nullable|integer|min:1|max:100000',
        ]);

        $resolutionDescription = trim((string) $validated['resolution_description']);
        $extraNotes = trim((string) ($validated['resolution_notes'] ?? ''));

        $resolutionNotes = "Acciones realizadas:\n" . $resolutionDescription;
        if ($extraNotes !== '') {
            $resolutionNotes .= "\n\nNotas adicionales:\n" . $extraNotes;
        }

        $data = [
            'resolution_notes' => $resolutionNotes,
            'actual_resolution_time' => isset($validated['actual_resolution_time'])
                ? (int) $validated['actual_resolution_time']
                : null,
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

        $technicians = User::with([
                'technician' => function ($query) {
                    $query->withCount([
                        'tasks as open_tasks_count' => function ($taskQuery) {
                            $taskQuery->whereNotIn('status', ['completed', 'cancelled']);
                        },
                    ]);
                },
                'technician.companies' => function ($query) use ($service_request) {
                    $query->where('companies.id', $service_request->company_id)->select('companies.id', 'name');
                },
            ])
            ->whereHas('technician', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('technician.companies', function ($query) use ($service_request) {
                $query->where('companies.id', $service_request->company_id);
            })
            ->where('id', '!=', $service_request->assigned_to)
            ->orderBy('name')
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
            'assigned_to' => [
                'required',
                'exists:users,id',
                Rule::exists('technicians', 'user_id')
                    ->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')),
            ],
            'reassignment_reason' => 'required|string|min:10|max:500',
        ]);

        if (!$this->isTechnicianAssignedToCompany((int) $validated['assigned_to'], (int) $service_request->company_id)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'El técnico seleccionado no está habilitado para esta entidad.',
            ]);
        }

        try {
            DB::transaction(function () use ($validated, $service_request) {
                $previousTechnician = $service_request->assigned_to;

                $service_request->update([
                    'assigned_to' => $validated['assigned_to'],
                ]);

                $this->serviceRequestService->syncTasksTechnician($service_request, (int) $validated['assigned_to']);

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('service-requests.show', $serviceRequest)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function resume(Request $request, ServiceRequest $serviceRequest)
    {
        $result = $this->workflowService->resumeRequest($serviceRequest);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

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

        // Si la solicitud está excluida de reportes, exigir cierre con tareas completas.
        // Si NO está excluida de reportes, no aplicar esta restricción.
        $isExcludedFromReports = $serviceRequest->is_reportable === false;
        if ($isExcludedFromReports && !$isVencimiento) {
            $pendingTasksCount = $serviceRequest->tasks()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            if ($pendingTasksCount > 0) {
                return redirect()
                    ->route('service-requests.show', $serviceRequest->id)
                    ->with('error', 'No se puede cerrar la solicitud excluida de reportes mientras existan tareas sin completar.');
            }
        }

        // Validaciones de cierre + evidencias opcionales
        $rules = $isVencimiento
            ? ['closure_reason' => 'required|string|min:10']
            : ['resolution_description' => 'sometimes|string|min:0'];

        $rules = array_merge($rules, [
            'evidence_type' => 'nullable|array',
            'evidence_type.*' => 'nullable|in:ARCHIVO,ENLACE',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|max:10240',
            'link_url' => 'nullable|array',
            'link_url.*' => 'nullable|url|max:2000',
        ]);

        $validated = $request->validate($rules);

        // Paso obligatorio previo al cierre normal:
        // Debe existir un resumen de actividades registrado durante la resolución.
        if ($isCierreNormal) {
            $existingSummary = trim((string) ($serviceRequest->resolution_notes ?? ''));
            if ($existingSummary === '') {
                return redirect()
                    ->route('service-requests.show', $serviceRequest->id)
                    ->with('error', 'Antes de cerrar, registra el resumen de actividades al resolver la solicitud.');
            }
        }

        $evidenceTypes = $request->input('evidence_type', []);
        $linkUrls = $request->input('link_url', []);
        $files = $request->file('files', []);

        foreach ($evidenceTypes as $idx => $type) {
            if (!$type) {
                continue;
            }
            if ($type === 'ARCHIVO' && empty($files[$idx])) {
                return redirect()
                    ->route('service-requests.show', $serviceRequest->id)
                    ->with('error', 'Debes adjuntar un archivo para cada evidencia de tipo Archivo.');
            }
            if ($type === 'ENLACE' && empty($linkUrls[$idx])) {
                return redirect()
                    ->route('service-requests.show', $serviceRequest->id)
                    ->with('error', 'Debes ingresar un enlace para cada evidencia de tipo Enlace.');
            }
        }

        $hasExistingEvidenceForClose = $serviceRequest->evidences()
            ->whereIn('evidence_type', ['ARCHIVO', 'PASO_A_PASO', 'ENLACE'])
            ->exists();

        $hasIncomingEvidenceForClose = false;
        foreach ($evidenceTypes as $idx => $type) {
            if ($type === 'ARCHIVO' && !empty($files[$idx])) {
                $hasIncomingEvidenceForClose = true;
                break;
            }
            if ($type === 'ENLACE' && !empty($linkUrls[$idx])) {
                $hasIncomingEvidenceForClose = true;
                break;
            }
        }

        if (!$isVencimiento && !$hasExistingEvidenceForClose && !$hasIncomingEvidenceForClose) {
            return redirect()
                ->route('service-requests.show', $serviceRequest->id)
                ->with('error', 'No se puede cerrar la solicitud si no hay evidencias.');
        }

        try {
            \DB::beginTransaction();

            if ($isVencimiento) {
                $tasksToCancel = $serviceRequest->tasks()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->get();

                foreach ($tasksToCancel as $task) {
                    $task->update([
                        'status' => 'cancelled',
                        'completed_at' => null,
                        'actual_duration_minutes' => null,
                        'actual_hours' => null,
                    ]);

                    $task->addHistory(
                        'cancelled',
                        auth()->id(),
                        'Tarea cancelada automáticamente por cierre por vencimiento de la solicitud asociada.'
                    );
                }
            }

            $updateData = [
                'status' => 'CERRADA',
                'closed_at' => now(),
                'updated_at' => now(),
            ];

            // Construir notas según el tipo de cierre
            $currentNotes = $serviceRequest->resolution_notes ?? '';

            if ($isVencimiento) {
                $closureDetails = "\n\n=== CIERRE POR VENCIMIENTO ===\n"
                    . 'Fecha/Hora: ' . now()->format('d/m/Y H:i:s') . "\n"
                    . 'Usuario: ID ' . auth()->id() . "\n"
                    . 'Motivo: ' . $request->closure_reason;
            } else {
                $closureDetails = "\n\n=== CIERRE NORMAL ===\n"
                    . 'Fecha/Hora: ' . now()->format('d/m/Y H:i:s') . "\n"
                    . 'Usuario: ID ' . auth()->id();

                if ($request->resolution_description) {
                    $closureDetails .= "\nDescripción: " . $request->resolution_description;
                }
            }

            $updateData['resolution_notes'] = trim($currentNotes . $closureDetails);

            // Crear evidencias si fueron enviadas desde el cierre
            if (!empty($evidenceTypes)) {
                foreach ($evidenceTypes as $idx => $type) {
                    if (!$type) {
                        continue;
                    }

                    $autoTitle = 'Evidencia cierre ' . $serviceRequest->ticket_number . ' - ' . now()->format('Ymd-His') . '-' . ($idx + 1);

                    if ($type === 'ARCHIVO' && !empty($files[$idx])) {
                        $result = $this->evidenceService->uploadEvidences($serviceRequest, [$files[$idx]]);

                        if (($result['success_count'] ?? 0) < 1) {
                            throw new \Exception('No se pudo subir la evidencia.');
                        }

                        $uploaded = $result['uploaded'][0] ?? null;
                        if ($uploaded) {
                            $uploaded->update([
                                'title' => $autoTitle,
                                'description' => 'Evidencia adjunta al cierre de la solicitud.',
                            ]);
                        }
                    }

                    if ($type === 'ENLACE') {
                        $url = $linkUrls[$idx] ?? null;
                        if (!$url) {
                            throw new \Exception('Enlace inválido.');
                        }

                        ServiceRequestEvidence::create([
                            'service_request_id' => $serviceRequest->id,
                            'title' => $autoTitle,
                            'description' => $url,
                            'evidence_type' => 'ENLACE',
                            'evidence_data' => [
                                'url' => $url,
                            ],
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Actualizar la solicitud
            \DB::table('service_requests')->where('id', $serviceRequest->id)->update($updateData);

            // Registrar en el historial
            if (class_exists('App\Models\ServiceRequestHistory')) {
                $actionType = $isVencimiento ? 'CIERRE_POR_VENCIMIENTO' : 'CIERRE_NORMAL';
                $description = $isVencimiento
                    ? 'Solicitud cerrada por vencimiento del plazo - ' . $request->closure_reason
                    : 'Solicitud cerrada normalmente';

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

    public function closeByVencimiento(Request $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'PAUSADA') {
            return redirect()
                ->route('service-requests.show', $serviceRequest->id)
                ->with('error', 'Solo se pueden cerrar por vencimiento solicitudes en estado PAUSADA.');
        }

        return $this->close($request, $serviceRequest);
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

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'exists:users,id',
                Rule::exists('technicians', 'user_id')
                    ->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')),
            ],
            'accept_and_start' => ['nullable', 'boolean'],
        ], [
            'assigned_to.required' => 'Debes seleccionar un técnico.',
            'assigned_to.exists' => 'El técnico seleccionado no es válido.',
            'accept_and_start.boolean' => 'El valor de aceptar e iniciar no es válido.',
        ]);

        if (!$this->isTechnicianAssignedToCompany((int) $validated['assigned_to'], (int) $service_request->company_id)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'El técnico seleccionado no está habilitado para esta entidad.',
                ],
                422,
            );
        }

        try {
            $acceptAndStart = (bool) ($validated['accept_and_start'] ?? false);

            $service_request->update([
                'assigned_to' => $validated['assigned_to'],
            ]);

            $this->serviceRequestService->syncTasksTechnician($service_request, (int) $validated['assigned_to']);

            if (class_exists('App\Models\ServiceRequestHistory')) {
                \App\Models\ServiceRequestHistory::create([
                    'service_request_id' => $service_request->id,
                    'user_id' => auth()->id(),
                    'action' => 'ASIGNACIÓN_RÁPIDA',
                    'description' => 'Solicitud asignada a técnico mediante asignación rápida',
                    'details' => [
                        'assigned_to' => $validated['assigned_to'],
                        'assigned_by' => auth()->id(),
                        'accept_and_start' => $acceptAndStart,
                    ],
                ]);
            }

            if ($acceptAndStart) {
                $service_request->refresh();

                if ($service_request->status === 'PENDIENTE') {
                    $acceptResult = $this->workflowService->acceptRequest($service_request);
                    if (!($acceptResult['success'] ?? false)) {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Técnico asignado, pero no se pudo aceptar la solicitud: ' . ($acceptResult['message'] ?? 'Error desconocido.'),
                            ],
                            422,
                        );
                    }
                }

                $service_request->refresh();
                if ($service_request->status === 'ACEPTADA') {
                    $startResult = $this->workflowService->startProcessing($service_request, false);
                    if (!($startResult['success'] ?? false)) {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Técnico asignado y solicitud aceptada, pero no se pudo iniciar: ' . ($startResult['message'] ?? 'Error desconocido.'),
                            ],
                            422,
                        );
                    }
                }
            }

            $service_request->refresh()->load('assignee');
            $acceptedAndStarted = $acceptAndStart && $service_request->status === 'EN_PROCESO';

            return response()->json([
                'success' => true,
                'message' => $acceptedAndStarted
                    ? 'Técnico asignado, solicitud aceptada e iniciada correctamente.'
                    : 'Técnico asignado correctamente',
                'assigned_to' => $service_request->assignee->name,
                'assigned_to_technician_id' => $service_request->assignee?->technician?->id,
                'assigned_to_email' => $service_request->assignee?->getEmailForCompany((int) $service_request->company_id),
                'assigned_to_position' => $service_request->assignee?->getPositionForCompany((int) $service_request->company_id),
                'status' => $service_request->status,
                'accepted_and_started' => $acceptedAndStarted,
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
            $requesterCompanyId = \App\Models\Requester::where('id', $validated['requester_id'])->value('company_id');
            if ($requesterCompanyId && (int) $requesterCompanyId !== (int) $service_request->company_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'El solicitante no pertenece al espacio de trabajo de esta solicitud.',
                    ],
                    422,
                );
            }

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
                'requester_email' => $service_request->requester->email ?? null,
                'requester_position' => $service_request->requester->position ?? null,
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

    private function isTechnicianAssignedToCompany(int $userId, int $companyId): bool
    {
        if ($userId <= 0 || $companyId <= 0) {
            return false;
        }

        return Technician::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereHas('companies', function ($query) use ($companyId) {
                $query->where('companies.id', $companyId);
            })
            ->exists();
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

    /**
     * Recalcular el corte asociado a una solicitud según la fecha de asignación aceptada del técnico.
     */
    public function updateCut(Request $request, ServiceRequest $serviceRequest)
    {
        try {
            $validated = $request->validate([
                'cut_id' => 'nullable|exists:cuts,id',
            ]);

            $resolvedCut = $this->serviceRequestService->resolveCutByTechnicianAssignmentDate($serviceRequest);

            if (!empty($validated['cut_id'])) {
                $cut = \App\Models\Cut::find($validated['cut_id']);
                if (!$resolvedCut || !$cut || (int) $resolvedCut->id !== (int) $cut->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El corte seleccionado no corresponde a la fecha de asignación aceptada del técnico.',
                    ], 422);
                }

            }

            $this->serviceRequestService->syncCutAssociationByTechnicianAssignmentDate($serviceRequest);

            return response()->json([
                'success' => true,
                'message' => 'Corte recalculado por fecha de asignación aceptada del técnico',
                'cut' => $resolvedCut ? [
                    'id' => $resolvedCut->id,
                    'name' => $resolvedCut->name,
                    'start_date' => $resolvedCut->start_date->format('d/m/Y'),
                    'end_date' => $resolvedCut->end_date->format('d/m/Y'),
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar corte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el corte: ' . $e->getMessage(),
            ], 500);
        }
    }
}
