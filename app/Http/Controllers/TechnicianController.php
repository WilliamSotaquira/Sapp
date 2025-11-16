<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    /**
     * Listado de técnicos
     */
    public function index()
    {
        $technicians = Technician::with(['user', 'skills'])
            ->paginate(20);

        return view('technicians.index', compact('technicians'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $users = User::whereDoesntHave('technician')->get();

        return view('technicians.create', compact('users'));
    }

    /**
     * Guardar nuevo técnico
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:technicians',
            'specialization' => 'required|string|in:frontend,backend,fullstack,devops,support,qa',
            'years_experience' => 'required|numeric|min:0|max:50',
            'skill_level' => 'required|in:junior,mid,senior,lead',
            'max_daily_capacity_hours' => 'required|numeric|min:1|max:12',
            'status' => 'required|in:active,inactive',
            'availability_status' => 'required|in:available,busy,on_leave,unavailable',
            'skills' => 'nullable|array',
            'skills.*.skill_name' => 'nullable|string|max:255',
            'skills.*.proficiency_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'skills.*.years_experience_skill' => 'nullable|numeric|min:0|max:50',
        ]);

        $technician = Technician::create($validated);

        // Agregar habilidades si se proporcionaron
        if (!empty($validated['skills'])) {
            foreach ($validated['skills'] as $skill) {
                if (!empty($skill['skill_name'])) {
                    $technician->skills()->create([
                        'skill_name' => $skill['skill_name'],
                        'proficiency_level' => $skill['proficiency_level'] ?? 'intermediate',
                        'years_experience' => $skill['years_experience_skill'] ?? 0,
                        'is_primary' => false,
                    ]);
                }
            }
        }

        // Crear regla de capacidad por defecto basada en el modelo 2+6
        $technician->capacityRules()->create([
            'day_type' => 'weekday',
            'max_impact_tasks_morning' => 2,
            'max_regular_tasks_afternoon' => 6,
            'impact_task_duration_minutes' => 90,
            'regular_task_duration_minutes' => 25,
            'buffer_between_tasks_minutes' => 5,
            'documentation_time_minutes' => 30,
            'is_active' => true,
        ]);

        return redirect()->route('technicians.show', $technician)
            ->with('success', 'Técnico creado exitosamente');
    }

    /**
     * Mostrar técnico específico
     */
    public function show(Technician $technician)
    {
        $technician->load(['user', 'skills', 'capacityRules']);

        // Métricas del técnico
        $stats = [
            'total_tasks' => $technician->tasks()->count(),
            'completed_tasks' => $technician->tasks()->completed()->count(),
            'in_progress_tasks' => $technician->tasks()->inProgress()->count(),
            'pending_tasks' => $technician->tasks()->pending()->count(),
            'avg_completion_time' => $technician->tasks()->completed()->avg('actual_duration_minutes'),
        ];

        return view('technicians.show', compact('technician', 'stats'));
    }

    /**
     * Editar técnico
     */
    public function edit(Technician $technician)
    {
        return view('technicians.edit', compact('technician'));
    }

    /**
     * Actualizar técnico
     */
    public function update(Request $request, Technician $technician)
    {
        $validated = $request->validate([
            'specialization' => 'required|string|in:frontend,backend,fullstack,devops,support,qa',
            'years_experience' => 'required|numeric|min:0|max:50',
            'skill_level' => 'required|in:junior,mid,senior,lead',
            'max_daily_capacity_hours' => 'required|numeric|min:1|max:12',
            'status' => 'required|in:active,inactive',
            'availability_status' => 'required|in:available,busy,on_leave,unavailable',
            'skills' => 'nullable|array',
            'skills.*.id' => 'nullable|exists:technician_skills,id',
            'skills.*.skill_name' => 'nullable|string|max:255',
            'skills.*.proficiency_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'skills.*.years_experience_skill' => 'nullable|numeric|min:0|max:50',
        ]);

        $technician->update($validated);

        // Actualizar o crear habilidades
        if (!empty($validated['skills'])) {
            foreach ($validated['skills'] as $skillData) {
                if (!empty($skillData['skill_name'])) {
                    if (!empty($skillData['id'])) {
                        // Actualizar habilidad existente
                        $skill = $technician->skills()->find($skillData['id']);
                        if ($skill) {
                            $skill->update([
                                'skill_name' => $skillData['skill_name'],
                                'proficiency_level' => $skillData['proficiency_level'] ?? 'intermediate',
                                'years_experience' => $skillData['years_experience_skill'] ?? 0,
                            ]);
                        }
                    } else {
                        // Crear nueva habilidad
                        $technician->skills()->create([
                            'skill_name' => $skillData['skill_name'],
                            'proficiency_level' => $skillData['proficiency_level'] ?? 'intermediate',
                            'years_experience' => $skillData['years_experience_skill'] ?? 0,
                            'is_primary' => false,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('technicians.show', $technician)
            ->with('success', 'Técnico actualizado exitosamente');
    }

    /**
     * Eliminar técnico
     */
    public function destroy(Technician $technician)
    {
        $technician->delete();

        return redirect()->route('technicians.index')
            ->with('success', 'Técnico eliminado exitosamente');
    }

    /**
     * Gestionar skills del técnico
     */
    public function skills(Technician $technician)
    {
        $skills = $technician->skills()->get();

        return view('technicians.skills', compact('technician', 'skills'));
    }

    /**
     * Agregar skill
     */
    public function addSkill(Request $request, Technician $technician)
    {
        $validated = $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'required|integer|min:0',
            'is_primary' => 'boolean',
        ]);

        $technician->skills()->create($validated);

        return back()->with('success', 'Skill agregado exitosamente');
    }

    /**
     * Dashboard de capacidad del técnico
     */
    public function capacity(Technician $technician)
    {
        $date = request('date', now()->format('Y-m-d'));

        $tasks = $technician->getTasksForDate($date);
        $availableCapacity = $technician->getAvailableCapacityForDate($date);
        $scheduleBlocks = $technician->scheduleBlocks()->forDate($date)->get();

        return view('technicians.capacity', compact('technician', 'tasks', 'availableCapacity', 'scheduleBlocks', 'date'));
    }
}
