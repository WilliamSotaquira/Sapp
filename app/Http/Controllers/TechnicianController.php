<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TechnicianController extends Controller
{
    /**
     * Listado de técnicos
     */
    public function index()
    {
        $currentCompanyId = (int) session('current_company_id');

        $technicians = Technician::with(['user', 'skills'])
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('user.companies', function ($q) use ($currentCompanyId) {
                    $q->where('companies.id', $currentCompanyId);
                });
            })
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->orderBy('users.name')
            ->select('technicians.*')
            ->paginate(20);

        return view('technicians.index', compact('technicians'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $currentCompanyId = (int) session('current_company_id');

        $users = User::whereDoesntHave('technician')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('companies', function ($q) use ($currentCompanyId) {
                    $q->where('companies.id', $currentCompanyId);
                });
            })
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            $users = User::whereDoesntHave('technician')
                ->orderBy('name')
                ->get();
        }

        $companies = Company::query()->orderBy('name')->get(['id', 'name']);
        $selectedCompanyIds = old('company_ids', $currentCompanyId ? [$currentCompanyId] : []);

        return view('technicians.create', compact('users', 'companies', 'selectedCompanyIds'));
    }

    /**
     * Guardar nuevo técnico
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id',
            'specialization' => 'required|string|in:frontend,backend,fullstack,devops,support,qa',
            'years_experience' => 'required|numeric|min:0|max:50',
            'skill_level' => 'required|in:junior,mid,senior,lead',
            'max_daily_capacity_hours' => 'required|numeric|min:1|max:12',
            'status' => 'required|in:active,inactive',
            'availability_status' => 'required|in:available,busy,on_leave,unavailable',
            'user_role' => 'nullable|in:user,technician,admin',
            'skills' => 'nullable|array',
            'skills.*.skill_name' => 'nullable|string|max:255',
            'skills.*.proficiency_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'skills.*.years_experience_skill' => 'nullable|numeric|min:0|max:50',
        ]);

        $selectedUser = User::findOrFail($validated['user_id']);
        $selectedUser->companies()->sync($validated['company_ids']);

        $existingTechnician = Technician::query()->where('user_id', $selectedUser->id)->first();
        if ($existingTechnician) {
            return redirect()
                ->route('technicians.show', $existingTechnician)
                ->with('success', 'El usuario ya tenía perfil técnico. Se vinculó a la entidad activa.');
        }

        $technician = Technician::create([
            'user_id' => $selectedUser->id,
            'specialization' => $validated['specialization'],
            'years_experience' => $validated['years_experience'],
            'skill_level' => $validated['skill_level'],
            'max_daily_capacity_hours' => $validated['max_daily_capacity_hours'],
            'status' => $validated['status'],
            'availability_status' => $validated['availability_status'],
        ]);

        // Asignar rol al usuario si el usuario actual es admin
        if (auth()->user()->isAdmin() && isset($validated['user_role'])) {
            $technician->user->update(['role' => $validated['user_role']]);
        }

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
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

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
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

        $companies = Company::query()->orderBy('name')->get(['id', 'name']);
        $selectedCompanyIds = old('company_ids', $technician->user->companies()->pluck('companies.id')->all());

        return view('technicians.edit', compact('technician', 'companies', 'selectedCompanyIds'));
    }

    /**
     * Actualizar técnico
     */
    public function update(Request $request, Technician $technician)
    {
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($technician->user_id)],
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id',
            'specialization' => 'required|string|in:frontend,backend,fullstack,devops,support,qa',
            'years_experience' => 'required|numeric|min:0|max:50',
            'skill_level' => 'required|in:junior,mid,senior,lead',
            'max_daily_capacity_hours' => 'required|numeric|min:1|max:12',
            'status' => 'required|in:active,inactive',
            'availability_status' => 'required|in:available,busy,on_leave,unavailable',
            'user_role' => 'nullable|in:user,technician,admin',
            'skills' => 'nullable|array',
            'skills.*.id' => 'nullable|exists:technician_skills,id',
            'skills.*.skill_name' => 'nullable|string|max:255',
            'skills.*.proficiency_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'skills.*.years_experience_skill' => 'nullable|numeric|min:0|max:50',
        ]);

        $technician->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $technician->user->companies()->sync($validated['company_ids']);
        $technician->update([
            'specialization' => $validated['specialization'],
            'years_experience' => $validated['years_experience'],
            'skill_level' => $validated['skill_level'],
            'max_daily_capacity_hours' => $validated['max_daily_capacity_hours'],
            'status' => $validated['status'],
            'availability_status' => $validated['availability_status'],
        ]);

        // Actualizar rol del usuario si el usuario actual es admin y no está cambiando su propio rol
        if (auth()->user()->isAdmin() && isset($validated['user_role']) && auth()->id() !== $technician->user_id) {
            $technician->user->update(['role' => $validated['user_role']]);
        }

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
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

        $technician->delete();

        return redirect()->route('technicians.index')
            ->with('success', 'Técnico eliminado exitosamente');
    }

    /**
     * Gestionar skills del técnico
     */
    public function skills(Technician $technician)
    {
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

        $skills = $technician->skills()->get();

        return view('technicians.skills', compact('technician', 'skills'));
    }

    /**
     * Agregar skill
     */
    public function addSkill(Request $request, Technician $technician)
    {
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

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
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

        $date = request('date', now()->format('Y-m-d'));

        $tasks = $technician->getTasksForDate($date);
        $availableCapacity = $technician->getAvailableCapacityForDate($date);
        $scheduleBlocks = $technician->scheduleBlocks()->forDate($date)->get();

        return view('technicians.capacity', compact('technician', 'tasks', 'availableCapacity', 'scheduleBlocks', 'date'));
    }

    /**
     * Toggle rol de administrador
     */
    public function toggleAdmin(Technician $technician)
    {
        if ($redirect = $this->ensureTechnicianInCurrentCompany($technician)) {
            return $redirect;
        }

        $user = $technician->user;

        // Verificar que el usuario actual sea admin
        if (!auth()->user()->isAdmin()) {
            return back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        // No permitir que un usuario se quite sus propios permisos de admin
        if ($user->id === auth()->id() && $user->isAdmin()) {
            return back()->with('error', 'No puedes quitarte tus propios permisos de administrador.');
        }

        // Toggle del rol
        if ($user->isAdmin()) {
            $user->role = 'user';
            $message = "Se han removido los permisos de administrador de {$user->name}.";
        } else {
            $user->role = 'admin';
            $message = "{$user->name} ahora es administrador.";
        }

        $user->save();

        return back()->with('success', $message);
    }

    private function ensureTechnicianInCurrentCompany(Technician $technician)
    {
        $currentCompanyId = (int) session('current_company_id');
        if (!$currentCompanyId) {
            return null;
        }

        $belongsToCurrentCompany = $technician->user()
            ->whereHas('companies', function ($q) use ($currentCompanyId) {
                $q->where('companies.id', $currentCompanyId);
            })
            ->exists();

        if ($belongsToCurrentCompany) {
            return null;
        }

        return redirect()
            ->route('technicians.index')
            ->with('error', 'El técnico no pertenece a la entidad activa.');
    }
}
