<?php
// app/Http\Controllers\ServiceFamilyController.php

namespace App\Http\Controllers;

use App\Models\ServiceFamily;
use Illuminate\Http\Request;

class ServiceFamilyController extends Controller
{
    public function index()
    {
        // SOLUCIÓN: Cargar solo relaciones existentes
        $serviceFamilies = ServiceFamily::with(['services' => function($query) {
            $query->withCount('subServices')->active();
        }])
        ->withCount('services')
        ->ordered()
        ->active()
        ->get();

        return view('service-families.index', compact('serviceFamilies'));
    }

    public function create()
    {
        return view('service-families.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:service_families,code',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer'
        ]);

        // Asegurar valores por defecto
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ServiceFamily::create($validated);

        return redirect()->route('service-families.index')
            ->with('success', 'Familia de servicio creada exitosamente.');
    }

    public function show(ServiceFamily $serviceFamily)
    {
        // Cargar relaciones existentes
        $serviceFamily->load([
            'services' => function($query) {
                $query->with(['subServices' => function($q) {
                    $q->active()->ordered();
                }])->active()->ordered();
            }
        ]);

        return view('service-families.show', compact('serviceFamily'));
    }

    public function edit(ServiceFamily $serviceFamily)
    {
        return view('service-families.edit', compact('serviceFamily'));
    }

    public function update(Request $request, ServiceFamily $serviceFamily)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:service_families,code,' . $serviceFamily->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer'
        ]);

        $serviceFamily->update($validated);

        return redirect()->route('service-families.index')
            ->with('success', 'Familia de servicio actualizada exitosamente.');
    }

    public function destroy(ServiceFamily $serviceFamily)
    {
        // Verificar si tiene servicios asociados
        if ($serviceFamily->services()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar la familia porque tiene servicios asociados.');
        }

        $serviceFamily->delete();

        return redirect()->route('service-families.index')
            ->with('success', 'Familia de servicio eliminada exitosamente.');
    }
}
