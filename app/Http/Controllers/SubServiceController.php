<?php
// app/Http\Controllers\SubServiceController.php

namespace App\Http\Controllers;

use App\Models\SubService;
use App\Models\Service;
use App\Models\ServiceFamily;
use Illuminate\Http\Request;

class SubServiceController extends Controller
{
    public function index(Request $request)
    {
        $currentCompanyId = (int) session('current_company_id');
        $search = trim((string) $request->get('search', ''));
        $familyId = (int) $request->get('family_id', 0);
        $status = (string) $request->get('status', '');

        $subServices = SubService::with(['service.family.contract'])
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('service.family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('service', function ($serviceQuery) use ($search) {
                            $serviceQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereHas('family', function ($familyQuery) use ($search) {
                                    $familyQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%")
                                        ->orWhereHas('contract', function ($contractQuery) use ($search) {
                                            $contractQuery->where('number', 'like', "%{$search}%");
                                        });
                                });
                        });
                });
            })
            ->when($familyId > 0, function ($query) use ($familyId) {
                $query->whereHas('service.family', function ($q) use ($familyId) {
                    $q->where('id', $familyId);
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->ordered()
            ->paginate(10)
            ->withQueryString();

        $families = ServiceFamily::with('contract')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->whereHas('services.subServices')
            ->ordered()
            ->get();

        return view('sub-services.index', compact('subServices', 'search', 'families', 'familyId', 'status'));
    }
public function create()
{
    $services = Service::with('family.contract')
        ->when((int) session('current_company_id'), function ($query) {
            $query->whereHas('family.contract', function ($q) {
                $q->where('company_id', (int) session('current_company_id'));
            });
        })
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
        $currentCompanyId = (int) session('current_company_id');
        $services = Service::active()
            ->with('family.contract')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->ordered()
            ->get();
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
