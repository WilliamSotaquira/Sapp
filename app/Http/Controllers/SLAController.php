<?php

namespace App\Http\Controllers;

use App\Models\ServiceLevelAgreement;
use App\Models\ServiceFamily;
use App\Models\ServiceSubservice;
use App\Models\SubService; // Import faltante
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Import faltante
use Illuminate\Validation\Rule;

class SLAController extends Controller
{
    public function index()
    {
        try {
            $slas = ServiceLevelAgreement::with(['serviceSubservice', 'serviceFamily'])
                ->latest()
                ->paginate(10);

            return view('slas.index', compact('slas'));
        } catch (\Exception $e) {
            \Log::error('Error en SLAController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la lista de SLAs: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

            $serviceFamilies = ServiceFamily::where('is_active', true)
                ->orderBy('name')
                ->get();

            $serviceSubservices = ServiceSubservice::with([
                'serviceFamily:id,name',
                'service:id,name',
                'subService:id,name'
            ])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            \Log::info('Service Subservices for create form:', [
                'count' => $serviceSubservices->count(),
                'data' => $serviceSubservices->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'service_family' => $item->serviceFamily->name ?? 'N/A',
                    'service' => $item->service->name ?? 'N/A',
                    'sub_service' => $item->subService->name ?? 'N/A'
                ])->toArray()
            ]);

            return view('slas.create', compact(
                'serviceFamilies',
                'serviceSubservices',
                'criticalityLevels'
            ));
        } catch (\Exception $e) {
            \Log::error('Error in SLAController@create: ' . $e->getMessage());
            return redirect()->route('slas.index')
                ->with('error', 'Error al cargar el formulario de creación: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'service_family_id' => 'required|exists:service_families,id',
                'service_subservice_id' => 'required|exists:service_subservices,id',
                'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
                'response_time_hours' => 'required|integer|min:1',
                'resolution_time_hours' => 'required|integer|min:1',
                'acceptance_time_minutes' => 'required|integer|min:1',
                'response_time_minutes' => 'nullable|integer',
                'resolution_time_minutes' => 'nullable|integer',
                'availability_percentage' => 'required|numeric|min:0|max:100',
                'conditions' => 'nullable|string',
                'is_active' => 'sometimes|boolean'
            ]);

            $serviceSubservice = ServiceSubservice::find($validated['service_subservice_id']);
            if (!$serviceSubservice) {
                return response()->json([
                    'message' => 'El subservicio seleccionado no existe.'
                ], 422);
            }

            $validated['is_active'] = $request->has('is_active');

            if (empty($validated['response_time_minutes'])) {
                $validated['response_time_minutes'] = $validated['response_time_hours'] * 60;
            }

            if (empty($validated['resolution_time_minutes'])) {
                $validated['resolution_time_minutes'] = $validated['resolution_time_hours'] * 60;
            }

            ServiceLevelAgreement::create($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SLA creado exitosamente.'
                ]);
            }

            return redirect()->route('slas.index')
                ->with('success', 'SLA creado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error creating SLA: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error al crear el SLA: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el SLA: ' . $e->getMessage());
        }
    }

    public function show(ServiceLevelAgreement $sla)
    {
        try {
            $sla->load([
                'serviceSubservice.serviceFamily',
                'serviceSubservice.service',
                'serviceSubservice.subService',
                'serviceRequests.requester',
                'serviceRequests.subService'
            ]);

            return view('slas.show', compact('sla'));
        } catch (\Exception $e) {
            \Log::error('Error en SLAController@show: ' . $e->getMessage());
            return redirect()->route('slas.index')
                ->with('error', 'Error al cargar el detalle del SLA: ' . $e->getMessage());
        }
    }

    public function edit(ServiceLevelAgreement $sla)
    {
        try {
            $serviceFamilies = ServiceFamily::where('is_active', true)->get();
            $serviceSubservices = ServiceSubservice::with(['service', 'subService'])->get();
            $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

            $formattedTimes = [
                'acceptance' => $this->formatTimeDisplay($sla->acceptance_time_minutes),
                'response' => $this->formatTimeDisplay($sla->response_time_minutes),
                'resolution' => $this->formatTimeDisplay($sla->resolution_time_minutes),
            ];

            return view('slas.edit', compact('sla', 'serviceFamilies', 'serviceSubservices', 'criticalityLevels', 'formattedTimes'));
        } catch (\Exception $e) {
            \Log::error('Error en SLAController@edit: ' . $e->getMessage());
            return redirect()->route('slas.index')
                ->with('error', 'Error al cargar el formulario de edición: ' . $e->getMessage());
        }
    }

    public function update(Request $request, ServiceLevelAgreement $sla)
    {
        try {
            $validated = $request->validate([
                'service_family_id' => 'required|exists:service_families,id',
                'name' => 'required|string|max:255',
                'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
                'acceptance_time_minutes' => 'required|integer|min:1',
                'response_time_minutes' => 'required|integer|min:1',
                'resolution_time_minutes' => 'required|integer|min:1',
                'conditions' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validated['acceptance_time_minutes'] >= $validated['response_time_minutes']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'El tiempo de aceptación debe ser menor al tiempo de respuesta.');
            }

            if ($validated['response_time_minutes'] >= $validated['resolution_time_minutes']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'El tiempo de respuesta debe ser menor al tiempo de resolución.');
            }

            $validated['is_active'] = $request->has('is_active');

            $sla->update($validated);

            return redirect()->route('slas.show', $sla)
                ->with('success', 'SLA actualizado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error en SLAController@update: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el SLA: ' . $e->getMessage());
        }
    }

    public function destroy(ServiceLevelAgreement $sla)
    {
        if ($sla->serviceRequests()->count() > 0) {
            return redirect()->route('slas.index')
                ->with('error', 'No se puede eliminar el SLA porque tiene solicitudes asociadas.');
        }

        $sla->delete();

        return redirect()->route('slas.index')
            ->with('success', 'SLA eliminado exitosamente.');
    }

    private function formatTimeDisplay($minutes)
    {
        if (!$minutes) return '--';
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        $parts = [];
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($mins > 0) $parts[] = $mins . 'm';
        return $parts ? implode(' ', $parts) : '0m';
    }

    /**
     * Obtener SLAs por sub-servicio - CORREGIDO
     */
    public function getSLAsBySubService($subServiceId)
    {
        try {
            Log::info("=== OBTENIENDO SLAS PARA SUB-SERVICE ===");
            Log::info("Sub-service ID: " . $subServiceId);

            // Verificar que el sub-servicio existe
            $subServiceModel = SubService::find($subServiceId);
            if (!$subServiceModel) {
                Log::warning("Sub-service no encontrado: " . $subServiceId);
                return response()->json([], 200);
            }

            // Obtener SLAs activos para este sub-servicio
            // Usando ServiceLevelAgreement que es el modelo correcto
            $slas = ServiceLevelAgreement::where('sub_service_id', $subServiceId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'criticality_level', 'acceptance_time_minutes', 'response_time_minutes', 'resolution_time_minutes']);

            Log::info("SLAs encontrados: " . $slas->count());

            return response()->json($slas);
        } catch (\Exception $e) {
            Log::error('Error al obtener SLAs: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Error interno del servidor al cargar SLAs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear SLA desde el modal en solicitudes de servicio - VERSIÓN COMPATIBLE
     */
    public function storeFromModal(Request $request)
    {
        try {
            Log::info('=== CREANDO SLA DESDE MODAL ===');
            Log::info('Datos recibidos:', $request->all());

            // Validación básica
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'sub_service_id' => 'required|exists:sub_services,id',
                'criticality_level' => 'required|string|in:Crítico,Alto,Medio,Bajo',
                'acceptance_time_minutes' => 'required|integer|min:1',
                'response_time_minutes' => 'required|integer|min:1',
                'resolution_time_minutes' => 'required|integer|min:1',
                'description' => 'nullable|string'
            ]);

            Log::info('Datos validados:', $validated);

            // Buscar service_subservice relacionado
            $serviceSubservice = ServiceSubservice::where('sub_service_id', $validated['sub_service_id'])->first();

            if (!$serviceSubservice) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una relación de servicio para este sub-servicio'
                ], 404);
            }

            // Preparar datos para crear el SLA
            $slaData = [
                'name' => $validated['name'],
                'service_subservice_id' => $serviceSubservice->id,
                'service_family_id' => $serviceSubservice->service_family_id,
                'criticality_level' => $validated['criticality_level'],
                'acceptance_time_minutes' => $validated['acceptance_time_minutes'],
                'response_time_minutes' => $validated['response_time_minutes'],
                'resolution_time_minutes' => $validated['resolution_time_minutes'],
                'description' => $validated['description'] ?? null,
                'is_active' => true
            ];

            // Crear el SLA
            $sla = ServiceLevelAgreement::create($slaData);

            Log::info('SLA creado exitosamente:', $sla->toArray());

            // Retornar respuesta JSON exitosa
            return response()->json([
                'success' => true,
                'sla' => [
                    'id' => $sla->id,
                    'name' => $sla->name,
                    'criticality_level' => $sla->criticality_level,
                    'acceptance_time_minutes' => $sla->acceptance_time_minutes,
                    'response_time_minutes' => $sla->response_time_minutes,
                    'resolution_time_minutes' => $sla->resolution_time_minutes
                ],
                'message' => 'SLA creado exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación:', $e->errors());

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear SLA desde modal: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}
