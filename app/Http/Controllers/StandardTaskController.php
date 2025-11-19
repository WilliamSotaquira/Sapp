<?php

namespace App\Http\Controllers;

use App\Models\StandardTask;
use App\Models\StandardSubtask;
use App\Models\SubService;
use Illuminate\Http\Request;

class StandardTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = StandardTask::with(['subService.service', 'standardSubtasks']);

        // Filtros
        if ($request->has('sub_service_id') && $request->sub_service_id) {
            $query->where('sub_service_id', $request->sub_service_id);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        $standardTasks = $query->ordered()->paginate(15);
        $subServices = SubService::with('service')->active()->ordered()->get();

        return view('standard-tasks.index', compact('standardTasks', 'subServices'));
    }

    public function create()
    {
        $subServices = SubService::with('service')->active()->ordered()->get();
        return view('standard-tasks.create', compact('subServices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_service_id' => 'required|exists:sub_services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:impact,regular',
            'priority' => 'required|in:critical,high,medium,low',
            'estimated_hours' => 'required|numeric|min:0.1|max:99.99',
            'technical_complexity' => 'nullable|integer|min:1|max:5',
            'technologies' => 'nullable|string',
            'required_accesses' => 'nullable|string',
            'environment' => 'nullable|in:development,staging,production',
            'technical_notes' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'subtasks' => 'nullable|array',
            'subtasks.*.title' => 'required|string|max:255',
            'subtasks.*.description' => 'nullable|string',
            'subtasks.*.priority' => 'required|in:high,medium,low',
            'subtasks.*.order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $standardTask = StandardTask::create($validated);

        // Crear subtareas si existen
        if (!empty($validated['subtasks'])) {
            foreach ($validated['subtasks'] as $subtaskData) {
                $standardTask->standardSubtasks()->create([
                    'title' => $subtaskData['title'],
                    'description' => $subtaskData['description'] ?? null,
                    'priority' => $subtaskData['priority'],
                    'order' => $subtaskData['order'] ?? 0,
                    'is_active' => true,
                ]);
            }
        }

        return redirect()
            ->route('standard-tasks.show', $standardTask)
            ->with('success', 'Tarea predefinida creada exitosamente');
    }

    public function show(StandardTask $standardTask)
    {
        $standardTask->load(['subService.service', 'standardSubtasks']);
        return view('standard-tasks.show', compact('standardTask'));
    }

    public function edit(StandardTask $standardTask)
    {
        $standardTask->load('standardSubtasks');
        $subServices = SubService::with('service')->active()->ordered()->get();
        return view('standard-tasks.edit', compact('standardTask', 'subServices'));
    }

    public function update(Request $request, StandardTask $standardTask)
    {
        $validated = $request->validate([
            'sub_service_id' => 'required|exists:sub_services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:impact,regular',
            'priority' => 'required|in:critical,high,medium,low',
            'estimated_hours' => 'required|numeric|min:0.1|max:99.99',
            'technical_complexity' => 'nullable|integer|min:1|max:5',
            'technologies' => 'nullable|string',
            'required_accesses' => 'nullable|string',
            'environment' => 'nullable|in:development,staging,production',
            'technical_notes' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'subtasks' => 'nullable|array',
            'subtasks.*.id' => 'nullable|exists:standard_subtasks,id',
            'subtasks.*.title' => 'required|string|max:255',
            'subtasks.*.description' => 'nullable|string',
            'subtasks.*.priority' => 'required|in:high,medium,low',
            'subtasks.*.order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $standardTask->update($validated);

        // Actualizar subtareas
        if (isset($validated['subtasks'])) {
            $existingIds = [];

            foreach ($validated['subtasks'] as $subtaskData) {
                if (!empty($subtaskData['id'])) {
                    // Actualizar existente
                    $subtask = StandardSubtask::find($subtaskData['id']);
                    if ($subtask && $subtask->standard_task_id == $standardTask->id) {
                        $subtask->update([
                            'title' => $subtaskData['title'],
                            'description' => $subtaskData['description'] ?? null,
                            'priority' => $subtaskData['priority'],
                            'order' => $subtaskData['order'] ?? 0,
                        ]);
                        $existingIds[] = $subtask->id;
                    }
                } else {
                    // Crear nueva
                    $newSubtask = $standardTask->standardSubtasks()->create([
                        'title' => $subtaskData['title'],
                        'description' => $subtaskData['description'] ?? null,
                        'priority' => $subtaskData['priority'],
                        'order' => $subtaskData['order'] ?? 0,
                        'is_active' => true,
                    ]);
                    $existingIds[] = $newSubtask->id;
                }
            }

            // Eliminar subtareas que ya no están
            $standardTask->standardSubtasks()->whereNotIn('id', $existingIds)->delete();
        } else {
            // Si no hay subtareas, eliminar todas
            $standardTask->standardSubtasks()->delete();
        }

        return redirect()
            ->route('standard-tasks.show', $standardTask)
            ->with('success', 'Tarea predefinida actualizada exitosamente');
    }

    public function destroy(StandardTask $standardTask)
    {
        $standardTask->delete();

        return redirect()
            ->route('standard-tasks.index')
            ->with('success', 'Tarea predefinida eliminada exitosamente');
    }
}
