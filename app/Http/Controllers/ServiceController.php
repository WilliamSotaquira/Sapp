<?php
// app/Http\Controllers\ServiceController.php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceFamily;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $currentCompanyId = (int) session('current_company_id');

        $services = Service::with(['family.contract', 'subServices' => function($query) {
            $query->where('is_active', true);
        }])
        ->withCount(['subServices' => function($query) {
            $query->where('is_active', true);
        }])
        ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
            $query->whereHas('family.contract', function ($q) use ($currentCompanyId) {
                $q->where('company_id', $currentCompanyId);
            });
        })
        ->ordered()
        ->get();

        return view('services.index', compact('services'));
    }

    public function create()
    {
        $currentCompanyId = (int) session('current_company_id');
        $serviceFamilies = ServiceFamily::active()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->with('contract:id,number')
            ->ordered()
            ->get();
        return view('services.create', compact('serviceFamilies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_family_id' => 'required|exists:service_families,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:services,code',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer'
        ]);

        $validated['is_active'] = (bool)($request->is_active ?? false);
        $validated['order'] = $validated['order'] ?? 0;

        Service::create($validated);

        return redirect()->route('services.index')
            ->with('success', 'Servicio creado exitosamente.');
    }

    public function show(Service $service)
    {
        // CORRECCIÓN: Usar where() en lugar de active() en la relación
        $service->load([
            'family',
            'subServices' => function($query) {
                $query->where('is_active', true)->orderBy('order')->orderBy('name');
            }
        ]);

        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $currentCompanyId = (int) session('current_company_id');
        $serviceFamilies = ServiceFamily::active()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->with('contract:id,number')
            ->ordered()
            ->get();
        return view('services.edit', compact('service', 'serviceFamilies'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'service_family_id' => 'required|exists:service_families,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:services,code,' . $service->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer'
        ]);

        $validated['is_active'] = (bool)($request->is_active ?? false);

        $service->update($validated);

        return redirect()->route('services.index')
            ->with('success', 'Servicio actualizado exitosamente.');
    }

    public function destroy(Service $service)
    {
        if ($service->subServices()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar el servicio porque tiene sub-servicios asociados.');
        }

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Servicio eliminado exitosamente.');
    }
}
