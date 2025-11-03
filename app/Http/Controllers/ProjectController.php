<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Requirement;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount(['requirements', 'requirements as active_requirements_count' => function($query) {
            $query->whereIn('status', ['pending', 'in_progress']);
        }])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:projects',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,completed,cancelled,on_hold'
        ]);

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Proyecto creado exitosamente.');
    }

    public function show(Project $project)
    {
        $project->load(['requirements' => function($query) {
            $query->with(['reporter', 'classification', 'evidences'])
                  ->orderBy('created_at', 'desc');
        }]);

        $requirements = $project->requirements()->paginate(10);

        return view('projects.show', compact('project', 'requirements'));
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:projects,code,' . $project->id,
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,completed,cancelled,on_hold'
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Proyecto actualizado exitosamente.');
    }

    public function destroy(Project $project)
    {
        // Verificar que no tenga requerimientos asociados
        if ($project->requirements()->exists()) {
            return redirect()->route('projects.index')
                ->with('error', 'No se puede eliminar el proyecto porque tiene requerimientos asociados.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Proyecto eliminado exitosamente.');
    }

    public function updateProgress(Project $project)
    {
        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        return back()->with('success', 'Progreso del proyecto actualizado.');
    }
}
