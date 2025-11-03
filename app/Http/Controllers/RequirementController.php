<?php

namespace App\Http\Controllers;

use App\Models\Requirement;
use App\Models\Reporter;
use App\Models\Classification;
use App\Models\Project;
use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RequirementController extends Controller
{
    public function index(Request $request)
    {
        $query = Requirement::with(['reporter', 'classification', 'project', 'evidences']);

        // Filtros
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('classification_id') && $request->classification_id !== 'all') {
            $query->where('classification_id', $request->classification_id);
        }

        if ($request->has('project_id') && $request->project_id !== 'all') {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('reporter', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $requirements = $query->orderBy('created_at', 'desc')->paginate(10);
        $classifications = Classification::where('is_active', true)->get();
        $reporters = Reporter::where('is_active', true)->get();
        $projects = Project::where('status', 'active')->get();

        return view('requirements.index', compact(
            'requirements',
            'classifications',
            'reporters',
            'projects'
        ));
    }

    public function create()
    {
        $reporters = Reporter::where('is_active', true)->get();
        $classifications = Classification::where('is_active', true)->get();
        $projects = Project::where('status', 'active')->get();
        $parentRequirements = Requirement::whereNull('parent_id')->get();

        return view('requirements.create', compact(
            'reporters',
            'classifications',
            'projects',
            'parentRequirements'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'reporter_id' => 'required|exists:reporters,id',
            'classification_id' => 'required|exists:classifications,id',
            'project_id' => 'nullable|exists:projects,id',
            'parent_id' => 'nullable|exists:requirements,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string'
        ]);

        $requirement = Requirement::create($validated);

        return redirect()->route('requirements.show', $requirement)
            ->with('success', 'Requerimiento creado exitosamente.');
    }

    public function show(Requirement $requirement)
    {
        $requirement->load(['reporter', 'classification', 'project', 'evidences', 'children', 'parent']);
        return view('requirements.show', compact('requirement'));
    }

    public function edit(Requirement $requirement)
    {
        $reporters = Reporter::where('is_active', true)->get();
        $classifications = Classification::where('is_active', true)->get();
        $projects = Project::where('status', 'active')->get();
        $parentRequirements = Requirement::whereNull('parent_id')
            ->where('id', '!=', $requirement->id)
            ->get();

        return view('requirements.edit', compact(
            'requirement',
            'reporters',
            'classifications',
            'projects',
            'parentRequirements'
        ));
    }

    public function update(Request $request, Requirement $requirement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'reporter_id' => 'required|exists:reporters,id',
            'classification_id' => 'required|exists:classifications,id',
            'project_id' => 'nullable|exists:projects,id',
            'parent_id' => 'nullable|exists:requirements,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'completed_date' => 'nullable|date|required_if:status,completed',
            'progress' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string'
        ]);

        // Si se marca como completado, establecer fecha de completado
        if ($validated['status'] === 'completed' && empty($validated['completed_date'])) {
            $validated['completed_date'] = now();
        }

        // Si se desmarca completado, limpiar fecha
        if ($validated['status'] !== 'completed') {
            $validated['completed_date'] = null;
        }

        $requirement->update($validated);

        return redirect()->route('requirements.show', $requirement)
            ->with('success', 'Requerimiento actualizado exitosamente.');
    }

    public function destroy(Requirement $requirement)
    {
        // Eliminar evidencias primero
        foreach ($requirement->evidences as $evidence) {
            Storage::delete($evidence->file_path);
            $evidence->delete();
        }

        $requirement->delete();

        return redirect()->route('requirements.index')
            ->with('success', 'Requerimiento eliminado exitosamente.');
    }

    public function storeEvidence(Request $request, Requirement $requirement)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500'
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::random(40) . '.' . $extension;
                $filePath = $file->storeAs('evidences', $fileName, 'public');

                Evidence::create([
                    'requirement_id' => $requirement->id,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_type' => $extension,
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'description' => $request->description
                ]);
            }
        }

        return back()->with('success', 'Evidencias guardadas exitosamente.');
    }

    public function updateProgress(Request $request, Requirement $requirement)
    {
        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);

        $requirement->update([
            'progress' => $request->progress,
            'status' => $request->progress == 100 ? 'completed' : 'in_progress'
        ]);

        return back()->with('success', 'Progreso actualizado exitosamente.');
    }
}
