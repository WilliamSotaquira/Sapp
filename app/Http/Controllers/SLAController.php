<?php

namespace App\Http\Controllers;

use App\Models\ServiceLevelAgreement;
use App\Models\ServiceFamily;
use Illuminate\Http\Request;

class SLAController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $slas = ServiceLevelAgreement::with('serviceFamily')
            ->latest()
            ->paginate(10);

        return view('slas.index', compact('slas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $serviceFamilies = ServiceFamily::where('is_active', true)->get();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return view('slas.create', compact('serviceFamilies', 'criticalityLevels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_family_id' => 'required|exists:service_families,id',
            'name' => 'required|string|max:255',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
            'acceptance_time_minutes' => 'required|integer|min:1',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validar que los tiempos sean coherentes
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

        ServiceLevelAgreement::create($validated);

        return redirect()->route('slas.index')
            ->with('success', 'SLA creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceLevelAgreement $sla)
    {
        $sla->load(['serviceFamily', 'serviceRequests']);
        return view('slas.show', compact('sla'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceLevelAgreement $sla)
    {
        $serviceFamilies = ServiceFamily::where('is_active', true)->get();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return view('slas.edit', compact('sla', 'serviceFamilies', 'criticalityLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceLevelAgreement $sla)
    {
        $validated = $request->validate([
            'service_family_id' => 'required|exists:service_families,id',
            'name' => 'required|string|max:255',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
            'acceptance_time_minutes' => 'required|integer|min:1',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validar que los tiempos sean coherentes
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

        $sla->update($validated);

        return redirect()->route('slas.index')
            ->with('success', 'SLA actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
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
}
