<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    public function index()
    {
        $classifications = Classification::withCount('requirements')->orderBy('order')->paginate(10);
        return view('classifications.index', compact('classifications'));
    }

    public function create()
    {
        return view('classifications.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:classifications',
            'color' => 'required|string|max:7',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        Classification::create($validated);

        return redirect()->route('classifications.index')
            ->with('success', 'Clasificación creada exitosamente.');
    }

    public function show(Classification $classification)
    {
        $classification->load(['requirements' => function($query) {
            $query->with(['reporter', 'project'])
                  ->orderBy('created_at', 'desc');
        }]);

        $requirements = $classification->requirements()->paginate(10);

        return view('classifications.show', compact('classification', 'requirements'));
    }

    public function edit(Classification $classification)
    {
        return view('classifications.edit', compact('classification'));
    }

    public function update(Request $request, Classification $classification)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:classifications,name,' . $classification->id,
            'color' => 'required|string|max:7',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        $classification->update($validated);

        return redirect()->route('classifications.index')
            ->with('success', 'Clasificación actualizada exitosamente.');
    }

    public function destroy(Classification $classification)
    {
        // Verificar que no tenga requerimientos asociados
        if ($classification->requirements()->exists()) {
            return redirect()->route('classifications.index')
                ->with('error', 'No se puede eliminar la clasificación porque tiene requerimientos asociados.');
        }

        $classification->delete();

        return redirect()->route('classifications.index')
            ->with('success', 'Clasificación eliminada exitosamente.');
    }
}
