<?php
// app/Http\Controllers\ServiceFamilyController.php

namespace App\Http\Controllers;

use App\Models\ServiceFamily;
use Illuminate\Http\Request;

class ServiceFamilyController extends Controller
{
    public function index()
    {
        $currentCompanyId = (int) session('current_company_id');
        // SOLUCIÓN: Cargar solo relaciones existentes
        $serviceFamilies = ServiceFamily::with(['services' => function($query) {
            $query->withCount('subServices')->active();
        }])
        ->with(['contract:id,number,name,company_id'])
        ->withCount('services')
        ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
            $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                $q->where('company_id', $currentCompanyId);
            });
        })
        ->ordered()
        ->active()
        ->get();

        return view('service-families.index', compact('serviceFamilies'));
    }

    public function create()
    {
        $currentCompanyId = (int) session('current_company_id');
        $contracts = \App\Models\Contract::query()
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->orderBy('number')
            ->get(['id', 'number', 'name', 'company_id']);

        return view('service-families.create', compact('contracts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
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
        // Cargar servicios sin filtrar por activos para que "listado" y "totales" sean consistentes.
        $serviceFamily->load([
            'contract',
            'services' => function ($query) {
                $query->withCount(['subServices', 'activeSubServices'])->ordered();
            },
            'serviceLevelAgreements',
        ])->loadCount([
            'services',
            'services as active_services_count' => function ($query) {
                $query->where('is_active', true);
            },
            'serviceLevelAgreements',
            'serviceLevelAgreements as active_slas_count' => function ($query) {
                $query->where('is_active', true);
            },
        ]);

        return view('service-families.show', compact('serviceFamily'));
    }

    public function edit(ServiceFamily $serviceFamily)
    {
        $currentCompanyId = (int) session('current_company_id');
        $contracts = \App\Models\Contract::query()
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->orderBy('number')
            ->get(['id', 'number', 'name', 'company_id']);

        return view('service-families.edit', compact('serviceFamily', 'contracts'));
    }

    public function update(Request $request, ServiceFamily $serviceFamily)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
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
