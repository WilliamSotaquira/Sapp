<?php

namespace App\Http\Controllers;

use App\Models\Reporter;
use App\Support\DepartmentOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReporterController extends Controller
{
    public function index()
    {
        $reporters = Reporter::withCount('requirements')->orderBy('name')->paginate(10);
        return view('reporters.index', compact('reporters'));
    }

    public function create()
    {
        return view('reporters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:reporters',
            'department' => ['required', 'string', 'max:255', Rule::in(DepartmentOptions::all())],
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        Reporter::create($validated);

        return redirect()->route('reporters.index')
            ->with('success', 'Reportador creado exitosamente.');
    }

    public function show(Reporter $reporter)
    {
        $reporter->load(['requirements' => function($query) {
            $query->with(['classification', 'project'])
                  ->orderBy('created_at', 'desc');
        }]);

        $requirements = $reporter->requirements()->paginate(10);

        return view('reporters.show', compact('reporter', 'requirements'));
    }

    public function edit(Reporter $reporter)
    {
        return view('reporters.edit', compact('reporter'));
    }

    public function update(Request $request, Reporter $reporter)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:reporters,email,' . $reporter->id,
            'department' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($reporter) {
                    $value = is_string($value) ? trim($value) : $value;
                    $allowed = DepartmentOptions::all();
                    if (!in_array($value, $allowed, true) && $value !== $reporter->department) {
                        $fail('El departamento seleccionado no es válido.');
                    }
                },
            ],
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $reporter->update($validated);

        return redirect()->route('reporters.index')
            ->with('success', 'Reportador actualizado exitosamente.');
    }

    public function destroy(Reporter $reporter)
    {
        if ($reporter->requirements()->exists()) {
            return redirect()->route('reporters.index')
                ->with('error', 'No se puede eliminar el reportador porque tiene requerimientos asociados.');
        }

        $reporter->delete();

        return redirect()->route('reporters.index')
            ->with('success', 'Reportador eliminado exitosamente.');
    }
}
