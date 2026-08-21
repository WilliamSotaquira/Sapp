<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Listado de proyectos del workspace activo.
     */
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $query = Project::withCount(['serviceRequests'])
            ->where('company_id', $companyId)
            ->orderByRaw("FIELD(status, 'in_progress', 'active', 'on_hold', 'completed', 'cancelled')")
            ->orderBy('updated_at', 'desc');

        // Filtro por estado
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->paginate(15)->withQueryString();

        return view('projects.index', compact('projects'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Almacenar nuevo proyecto.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,in_progress,on_hold',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $companyId = (int) session('current_company_id');

        $project = Project::create([
            'name' => $validated['name'],
            'code' => Project::generateCode(),
            'description' => $validated['description'] ?? null,
            'company_id' => $companyId,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'expected_end_date' => $validated['expected_end_date'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyecto creado exitosamente.');
    }

    /**
     * Vista detalle del proyecto con solicitudes vinculadas.
     */
    public function show(Project $project)
    {
        $project->load(['serviceRequests' => function ($query) {
            $query->with(['subService', 'assignee', 'requester'])
                  ->orderByRaw("FIELD(status, 'EN_PROCESO', 'ACEPTADA', 'PENDIENTE', 'PAUSADA', 'REABIERTO', 'RESUELTA', 'CERRADA', 'CANCELADA')")
                  ->orderBy('priority_score', 'desc');
        }, 'creator']);

        // Solicitudes disponibles para vincular (del mismo workspace, no vinculadas a otro proyecto)
        $companyId = (int) session('current_company_id');
        $availableRequests = ServiceRequest::where('company_id', $companyId)
            ->whereNull('project_id')
            ->whereNotIn('status', ['CERRADA', 'CANCELADA'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'ticket_number', 'title', 'status', 'priority_level']);

        return view('projects.show', compact('project', 'availableRequests'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    /**
     * Actualizar proyecto.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:active,in_progress,completed,on_hold,cancelled',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($validated);

        // Si se marca como completado, registrar la fecha
        if ($validated['status'] === 'completed' && !$project->actual_end_date) {
            $project->update(['actual_end_date' => now()]);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyecto actualizado.');
    }

    /**
     * Vincular una solicitud al proyecto.
     */
    public function linkRequest(Request $request, Project $project)
    {
        $validated = $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
        ]);

        $sr = ServiceRequest::findOrFail($validated['service_request_id']);

        // Verificar que no esté ya vinculada a otro proyecto
        if ($sr->project_id && $sr->project_id !== $project->id) {
            return back()->with('error', 'La solicitud ya está vinculada a otro proyecto.');
        }

        $sr->update(['project_id' => $project->id]);

        return back()->with('success', "Solicitud {$sr->ticket_number} vinculada al proyecto.");
    }

    /**
     * Desvincular una solicitud del proyecto.
     */
    public function unlinkRequest(Project $project, ServiceRequest $serviceRequest)
    {
        if ((int) $serviceRequest->project_id !== (int) $project->id) {
            return back()->with('error', 'La solicitud no pertenece a este proyecto.');
        }

        $serviceRequest->update(['project_id' => null]);

        return back()->with('success', "Solicitud {$serviceRequest->ticket_number} desvinculada del proyecto.");
    }

    /**
     * Eliminar proyecto (solo si no tiene solicitudes vinculadas).
     */
    public function destroy(Project $project)
    {
        if ($project->serviceRequests()->exists()) {
            return back()->with('error', 'No se puede eliminar un proyecto con solicitudes vinculadas. Desvincula las solicitudes primero.');
        }

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Proyecto eliminado.');
    }
}
