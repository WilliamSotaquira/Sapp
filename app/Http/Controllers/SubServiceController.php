<?php
// app/Http\Controllers\SubServiceController.php

namespace App\Http\Controllers;

use App\Models\SubService;
use App\Models\Service;
use Illuminate\Http\Request;

class SubServiceController extends Controller
{
    public function index()
    {
        $subServices = SubService::with(['service.family'])
            ->active()
            ->ordered()
            ->paginate(10); // Cambia get() por paginate(10)

        return view('sub-services.index', compact('subServices'));
    }
public function create()
{
    $services = Service::with('family')
        ->active()
        ->ordered()
        ->get()
        ->groupBy('family_id'); // Agrupar por ID de familia

    return view('sub-services.create', compact('services'));
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:sub_services,code',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'cost' => 'nullable|numeric|min:0',
            'order' => 'sometimes|integer'
        ]);

        $validated['is_active'] = (bool)($request->is_active ?? true);
        $validated['order'] = $validated['order'] ?? 0;

        SubService::create($validated);

        return redirect()->route('sub-services.index')
            ->with('success', 'Sub-servicio creado exitosamente.');
    }

    public function show(SubService $subService)
    {
        // CORRECCIÓN: Cargar solo relaciones existentes
        $subService->load([
            'service.family',
            // Quitar serviceLevelAgreements si no existe
        ]);

        return view('sub-services.show', compact('subService'));
    }

    public function edit(SubService $subService)
    {
        $services = Service::active()->ordered()->get();
        return view('sub-services.edit', compact('subService', 'services'));
    }

    public function update(Request $request, SubService $subService)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:sub_services,code,' . $subService->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'cost' => 'nullable|numeric|min:0',
            'order' => 'sometimes|integer'
        ]);

        $validated['is_active'] = (bool)($request->is_active ?? true);

        $subService->update($validated);

        return redirect()->route('sub-services.index')
            ->with('success', 'Sub-servicio actualizado exitosamente.');
    }

    public function destroy(SubService $subService)
    {
        // Verificar si tiene solicitudes asociadas
        if ($subService->serviceRequests()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar el sub-servicio porque tiene solicitudes asociadas.');
        }

        $subService->delete();

        return redirect()->route('sub-services.index')
            ->with('success', 'Sub-servicio eliminado exitosamente.');
    }
}
