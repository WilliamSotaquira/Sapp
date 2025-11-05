<?php

namespace App\Http\Controllers;

use App\Models\ServiceLevelAgreement;
use App\Models\ServiceFamily;
use App\Models\ServiceSubservice;
use Illuminate\Http\Request;
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

    // En SLAController.php
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

            // Debug: Ver qué datos tenemos
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

            // Validar que service_subservice_id exista
            $serviceSubservice = ServiceSubservice::find($validated['service_subservice_id']);
            if (!$serviceSubservice) {
                return response()->json([
                    'message' => 'El subservicio seleccionado no existe.'
                ], 422);
            }

            $validated['is_active'] = $request->has('is_active');

            // Calcular minutos automáticamente si no se proporcionan
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
            // Cargar relaciones necesarias
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

            // Formatear tiempos para la vista
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

            // Validaciones de tiempos
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

            // Asegurar que is_active se procese correctamente
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

    // En SLAController.php, dentro de la clase
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
}
