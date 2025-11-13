<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use App\Models\ServiceLevelAgreement;
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
    public function index(Request $request)
    {
        $query = ServiceRequest::with(['subService.service.family', 'requester']);

        // Filtro de búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filtro de estado
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filtro de criticidad
        if ($request->has('criticality') && $request->criticality != '') {
            $query->where('criticality_level', $request->criticality);
        }

        $serviceRequests = $query->latest()->paginate(15);

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
        $subServices = SubService::with(['service.family', 'slas'])
            ->where('is_active', true)
            ->get();

        $users = User::all();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'URGENTE'];

        return view('service-requests.create', compact('subServices', 'users', 'criticalityLevels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('=== INICIANDO STORE ===');
        \Log::info('Datos RAW:', $request->all());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'sub_service_id' => 'required|exists:sub_services,id',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,URGENTE,CRITICA',
            'service_id' => 'required|exists:services,id',
            'family_id' => 'required|exists:service_families,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'requested_by' => 'required|exists:users,id',
            'web_routes' => 'required|string',
        ]);

        \Log::info('Datos validados:', $validated);

        // Procesar web_routes
        if (!empty($validated['web_routes'])) {
            $validated['web_routes'] = json_decode($validated['web_routes'], true) ?? [];
        }

        // NOTA: ticket_number se generará AUTOMÁTICAMENTE en el modelo
        // NO lo agregues manualmente aquí

        \Log::info('Datos finales para crear (sin ticket_number):', $validated);

        try {
            $serviceRequest = ServiceRequest::create($validated);
            \Log::info('✅ Solicitud creada:', [
                'id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
                'status' => $serviceRequest->status,
            ]);

            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('success', "Solicitud creada exitosamente! Ticket: {$serviceRequest->ticket_number}");
        } catch (\Exception $e) {
            \Log::error('❌ Error al crear solicitud: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

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
        $serviceRequest->load(['subService.service.family', 'sla', 'requester', 'assignee', 'breachLogs', 'evidences.user']);

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
    /**
     * Rechazar solicitud (versión corregida con withoutEvents)
     */
    public function reject(Request $request, ServiceRequest $serviceRequest)
    {
        \Log::info('=== 🔍 REJECT METHOD ===');
        \Log::info('Datos recibidos:', $request->all());

        // Verificar que la solicitud esté en estado PENDIENTE
        if ($serviceRequest->status !== 'PENDIENTE') {
            return redirect()->back()->with('error', 'La solicitud debe estar en estado PENDIENTE para ser rechazada.');
        }

        try {
            // Validar datos del formulario de rechazo
            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10|max:500',
            ]);

            // 🎯 Usar withoutEvents para evitar validaciones del trait
            ServiceRequest::withoutEvents(function () use ($serviceRequest, $validated) {
                $serviceRequest->update([
                    'status' => 'RECHAZADA',
                    'rejection_reason' => $validated['rejection_reason'],
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id(),
                ]);
            });

            \Log::info('🎉 ÉXITO: Solicitud rechazada exitosamente');

            // Crear evidencia del rechazo
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

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Solicitud rechazada correctamente.');
        } catch (ValidationException $e) {
            \Log::error('❌ ERROR de validación al rechazar:', $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ ERROR al rechazar: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al rechazar la solicitud: ' . $e->getMessage());
        }
    }

    public function start(ServiceRequest $serviceRequest)
    {
        \Log::info('🎯 === START METHOD CALLED ===');
        \Log::info('📦 Request ALL data: ' . json_encode(request()->all()));
        \Log::info('👤 User ID: ' . auth()->id());
        \Log::info('🔍 ServiceRequest ID: ' . $serviceRequest->id);
        \Log::info('🎫 Ticket: ' . $serviceRequest->ticket_number);
        \Log::info('📊 Status: ' . $serviceRequest->status);
        \Log::info('👥 Assigned_to: ' . $serviceRequest->assigned_to);
        \Log::info('🌐 URL: ' . request()->fullUrl());
        \Log::info('📝 Method: ' . request()->method());

        // Validaciones
        if ($serviceRequest->status !== 'ACEPTADA') {
            \Log::warning('❌ Validation failed: Status not ACEPTADA');
            return back()->with('error', 'La solicitud debe estar ACEPTADA para iniciar.');
        }

        if (!$serviceRequest->assigned_to) {
            \Log::warning('❌ Validation failed: No assigned technician');
            return back()->with('error', 'Asigna un técnico antes de iniciar.');
        }

        \Log::info('✅ All validations passed - Proceeding with start process...');

        // Procesamiento
        try {
            DB::transaction(function () use ($serviceRequest) {
                \Log::info('🔄 Starting database transaction');

                $previousStatus = $serviceRequest->status;

                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'started_at' => now(),
                ]);

                \Log::info('✅ ServiceRequest updated to EN_PROCESO');

                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Procesamiento Iniciado',
                    'description' => "Inicio de trabajo - Técnico: {$serviceRequest->assignee->name}",
                    'evidence_type' => 'SISTEMA',
                    'created_by' => auth()->id(),
                    'evidence_data' => [
                        'action' => 'STARTED',
                        'started_by' => auth()->id(),
                        'started_at' => now()->toISOString(),
                        'assigned_technician' => $serviceRequest->assigned_to,
                        'previous_status' => $previousStatus,
                        'new_status' => 'EN_PROCESO',
                    ],
                ]);

                \Log::info('✅ Evidence created successfully');
            });

            \Log::info('🎉 Process completed successfully for ticket: ' . $serviceRequest->ticket_number);
            return back()->with('success', 'Solicitud marcada como en proceso.');
        } catch (\Exception $e) {
            \Log::error("💥 Start processing failed: {$e->getMessage()}");
            \Log::error("📋 Stack trace: {$e->getTraceAsString()}");
            return back()->with('error', 'Error al iniciar el procesamiento.');
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

            // Preprocesar archivos para Base64 (incluyendo imágenes y otros archivos)
            $evidencesWithBase64 = $serviceRequest->evidences->map(function ($evidence) {
                // Solo procesar evidencias que tienen file_path y no son del tipo SISTEMA
                if ($evidence->file_path && $evidence->evidence_type !== 'SISTEMA') {
                    try {
                        // Buscar el archivo en diferentes ubicaciones posibles
                        $possiblePaths = [
                            storage_path('app/public/' . $evidence->file_path),
                            storage_path('app/public/evidences/' . basename($evidence->file_path)), // Nueva ruta
                            storage_path('app/' . $evidence->file_path),
                            public_path('storage/' . $evidence->file_path),
                            public_path($evidence->file_path),
                        ];

                        $filePath = null;
                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $filePath = $path;
                                break;
                            }
                        }

                        // Si no se encuentra, buscar por nombre de archivo en la carpeta evidences
                        if (!$filePath) {
                            $fileName = basename($evidence->file_path);
                            $evidencePath = storage_path('app/public/evidences/' . $fileName);
                            if (file_exists($evidencePath)) {
                                $filePath = $evidencePath;
                            }
                        }

                        if ($filePath && file_exists($filePath)) {
                            // Verificar si es una imagen por la extensión
                            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                            if (in_array($extension, $imageExtensions)) {
                                // Es una imagen - convertir a Base64
                                $imageData = base64_encode(file_get_contents($filePath));

                                $mimeTypes = [
                                    'jpg' => 'image/jpeg',
                                    'jpeg' => 'image/jpeg',
                                    'png' => 'image/png',
                                    'gif' => 'image/gif',
                                    'bmp' => 'image/bmp',
                                    'webp' => 'image/webp',
                                ];

                                $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';
                                $evidence->base64_content = "data:$mimeType;base64,$imageData";
                                $evidence->is_image = true;
                                $evidence->file_found = true;
                            } else {
                                // No es una imagen - marcar como archivo disponible
                                $evidence->is_image = false;
                                $evidence->file_found = true;
                                $evidence->base64_content = null;
                            }
                        } else {
                            $evidence->base64_content = null;
                            $evidence->is_image = false;
                            $evidence->file_found = false;
                            Log::warning("Archivo no encontrado: {$evidence->file_path}");
                            Log::warning('Buscando en: ' . storage_path('app/public/evidences/' . basename($evidence->file_path)));
                        }
                    } catch (\Exception $e) {
                        $evidence->base64_content = null;
                        $evidence->is_image = false;
                        $evidence->file_found = false;
                        Log::error("Error procesando archivo {$evidence->file_path}: " . $e->getMessage());
                    }
                } else {
                    $evidence->base64_content = null;
                    $evidence->is_image = false;
                    $evidence->file_found = false;
                }
                return $evidence;
            });

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

                    // Generar nombre único con servicios.code + Fecha + Hora + Microtime
                    $serviceCode = $serviceRequest->code ?? 'SR' . $serviceRequest->id;
                    $timestamp = now()->format('Ymd-His');
                    $microtime = substr(str_replace('.', '', microtime(true)), -6);
                    $extension = $file->getClientOriginalExtension();

                    // Limpiar el código de servicio
                    $cleanServiceCode = preg_replace('/[^a-zA-Z0-9]/', '-', $serviceCode);
                    $cleanServiceCode = substr($cleanServiceCode, 0, 20);

                    // Formato con guiones: ServicioCode-Fecha-Hora-Microtime
                    $fileName = "{$cleanServiceCode}-{$timestamp}-{$microtime}.{$extension}";

                    // Verificar y crear directorio si no existe
                    $directory = 'evidences';
                    if (!Storage::disk('public')->exists($directory)) {
                        Storage::disk('public')->makeDirectory($directory);
                        Log::info('Directorio creado: ' . $directory);
                    }

                    // Guardar archivo con verificación
                    try {
                        $filePath = $file->storeAs($directory, $fileName, 'public');
                        Log::info('Archivo guardado en:', ['path' => $filePath]);
                    } catch (\Exception $storageException) {
                        Log::error('Error al guardar archivo: ' . $storageException->getMessage());
                        continue;
                    }

                    // Verificar que el archivo se guardó correctamente
                    if (!Storage::disk('public')->exists($filePath)) {
                        Log::error('El archivo no existe en storage después de guardar: ' . $filePath);
                        continue;
                    }

                    // Verificar tamaño del archivo guardado
                    $storedFileSize = Storage::disk('public')->size($filePath);
                    Log::info('Archivo guardado - Tamaño original: ' . $file->getSize() . ', Tamaño guardado: ' . $storedFileSize);

                    if ($storedFileSize === 0) {
                        Log::error('El archivo se guardó con tamaño 0: ' . $filePath);
                        Storage::disk('public')->delete($filePath);
                        continue;
                    }

                    $evidenceData = [
                        'service_request_id' => $serviceRequest->id,
                        'title' => $fileName,
                        'description' => 'Archivo subido: ' . $file->getClientOriginalName(),
                        'evidence_type' => 'ARCHIVO',
                        'file_path' => $filePath,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_mime_type' => $file->getMimeType(),
                        'file_size' => $storedFileSize, // Usar el tamaño real del archivo guardado
                        'user_id' => auth()->id(),
                    ];

                    Log::info('Creando evidencia en BD:', $evidenceData);

                    try {
                        $evidence = ServiceRequestEvidence::create($evidenceData);
                        $evidence->load('user');
                        Log::info('✅ Evidencia creada con ID: ' . $evidence->id);
                        $uploadedFiles[] = $evidence;
                    } catch (\Exception $dbException) {
                        Log::error('Error al crear registro en BD: ' . $dbException->getMessage());
                        // Eliminar archivo si falla la BD
                        Storage::disk('public')->delete($filePath);
                        continue;
                    }
                }

                Log::info('=== SUBIDA COMPLETADA ===', [
                    'total_files' => count($uploadedFiles),
                    'service_request_id' => $serviceRequest->id,
                    'service_code' => $serviceRequest->code ?? 'SR' . $serviceRequest->id,
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
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()
                ->back()
                ->with('error', 'Error al subir archivos: ' . $e->getMessage());
        }
    }
}
