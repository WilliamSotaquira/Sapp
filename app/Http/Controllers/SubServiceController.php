<?php

namespace App\Http\Controllers;

use App\Models\SubService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subServices = SubService::with(['service.family'])
            ->latest()
            ->paginate(10);

        return view('sub-services.index', compact('subServices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = Service::with('family')
            ->where('is_active', true)
            ->get()
            ->groupBy('family.name');

        return view('sub-services.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        // Validar código único por servicio
        $exists = SubService::where('service_id', $validated['service_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El código ya existe para este servicio.');
        }

        SubService::create($validated);

        return redirect()->route('sub-services.index')
            ->with('success', 'Sub-servicio creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SubService $subService)
    {
        // Cargar todas las relaciones necesarias
        $subService->load([
            'service.family.serviceLevelAgreements' => function ($query) {
                $query->where('is_active', true);
            },
            'serviceRequests' => function ($query) {
                $query->latest()->take(10);
            }
        ]);

        return view('sub-services.show', compact('subService'));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubService $subService)
    {
        $services = Service::with('family')
            ->where('is_active', true)
            ->get()
            ->groupBy('family.name');

        return view('sub-services.edit', compact('subService', 'services'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubService $subService)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        // Validar código único por servicio (excluyendo el actual)
        $exists = SubService::where('service_id', $validated['service_id'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $subService->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El código ya existe para este servicio.');
        }

        $subService->update($validated);

        return redirect()->route('sub-services.index')
            ->with('success', 'Sub-servicio actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubService $subService)
    {
        if ($subService->serviceRequests()->count() > 0) {
            return redirect()->route('sub-services.index')
                ->with('error', 'No se puede eliminar el sub-servicio porque tiene solicitudes asociadas.');
        }

        $subService->delete();

        return redirect()->route('sub-services.index')
            ->with('success', 'Sub-servicio eliminado exitosamente.');
    }

    /**
     * Obtener sub-servicios de un servicio específico (para AJAX)
     */
    public function getByService(Service $service)
    {
        $subServices = $service->subServices()->where('is_active', true)->get();

        return response()->json($subServices);
    }
}
