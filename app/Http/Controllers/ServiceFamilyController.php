<?php

namespace App\Http\Controllers;

use App\Models\ServiceFamily;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceFamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceFamily::withCount(['services', 'serviceLevelAgreements']);

        // Filtro de búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Ordenamiento
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        if (in_array($sort, ['name', 'code', 'created_at'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $serviceFamilies = $query->paginate(10)->withQueryString();

        return view('service-families.index', compact('serviceFamilies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('service-families.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:service_families,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ServiceFamily::create($validated);

        return redirect()->route('service-families.index')
            ->with('success', 'Familia de servicio creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceFamily $serviceFamily)
    {
        $serviceFamily->load(['services.subServices', 'serviceLevelAgreements']);

        return view('service-families.show', compact('serviceFamily'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceFamily $serviceFamily)
    {
        return view('service-families.edit', compact('serviceFamily'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceFamily $serviceFamily)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('service_families')->ignore($serviceFamily->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $serviceFamily->update($validated);

        return redirect()->route('service-families.index')
            ->with('success', 'Familia de servicio actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceFamily $serviceFamily)
    {
        // Verificar si tiene servicios asociados
        if ($serviceFamily->services()->count() > 0) {
            return redirect()->route('service-families.index')
                ->with('error', 'No se puede eliminar la familia de servicio porque tiene servicios asociados.');
        }

        $serviceFamily->delete();

        return redirect()->route('service-families.index')
            ->with('success', 'Familia de servicio eliminada exitosamente.');
    }

    /**
     * Obtener servicios de una familia específica (para AJAX)
     */
    public function getServices(ServiceFamily $serviceFamily)
    {
        $services = $serviceFamily->services()->where('is_active', true)->get();

        return response()->json($services);
    }
}
