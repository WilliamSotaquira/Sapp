<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceFamily;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Service::with(['family'])
            ->withCount(['subServices', 'activeSubServices']);

        // Filtro de búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por familia
        if ($request->has('family') && $request->family != '') {
            $query->where('service_family_id', $request->family);
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
        $sort = $request->get('sort', 'order');
        $direction = $request->get('direction', 'asc');

        if (in_array($sort, ['name', 'code', 'order', 'created_at'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('order', 'asc')->orderBy('name', 'asc');
        }

        $services = $query->paginate(10)->withQueryString();
        $serviceFamilies = ServiceFamily::where('is_active', true)->get();

        return view('services.index', compact('services', 'serviceFamilies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $serviceFamilies = ServiceFamily::where('is_active', true)->get();
        return view('services.create', compact('serviceFamilies'));
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'service_family_id' => 'required|exists:service_families,id',
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:10',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
        'order' => 'integer|min:0',
    ]);

    // Validar código único por familia
    $exists = Service::where('service_family_id', $validated['service_family_id'])
        ->where('code', $validated['code'])
        ->exists();

    if ($exists) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'El código "' . $validated['code'] . '" ya existe para esta familia de servicio.');
    }

    // Asegurar que is_active sea booleano
    $validated['is_active'] = (bool)($validated['is_active'] ?? true);

    Service::create($validated);

    return redirect()->route('services.index')
        ->with('success', 'Servicio "' . $validated['name'] . '" creado exitosamente.');
}

    /**
     * Display the specified resource.
     */
public function show(Service $service)
{
    $service->load([
        'family',
        'subServices' => function($query) {
            $query->orderBy('order', 'asc')->orderBy('name', 'asc');
        },
        'activeSubServices'
    ]);

    return view('services.show', compact('service'));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        $serviceFamilies = ServiceFamily::where('is_active', true)->get();
        return view('services.edit', compact('service', 'serviceFamilies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'service_family_id' => 'required|exists:service_families,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        // Validar código único por familia (excluyendo el actual)
        $exists = Service::where('service_family_id', $validated['service_family_id'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $service->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El código ya existe para esta familia de servicio.');
        }

        $service->update($validated);

        return redirect()->route('services.index')
            ->with('success', 'Servicio actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        if ($service->subServices()->count() > 0) {
            return redirect()->route('services.index')
                ->with('error', 'No se puede eliminar el servicio porque tiene sub-servicios asociados.');
        }

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Servicio eliminado exitosamente.');
    }
}
