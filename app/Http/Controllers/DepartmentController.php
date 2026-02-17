<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    private function currentCompanyId(): int
    {
        return (int) session('current_company_id');
    }

    private function ensureWorkspace(Department $department): void
    {
        $companyId = $this->currentCompanyId();
        if ($companyId && (int) $department->company_id !== $companyId) {
            abort(403, 'No tienes acceso a este departamento.');
        }
    }

    public function index(Request $request)
    {
        $companyId = $this->currentCompanyId();
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', 'all');

        $departments = Department::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('requester-management.departments.index', compact('departments', 'search', 'status'));
    }

    public function create()
    {
        return view('requester-management.departments.create');
    }

    public function store(Request $request)
    {
        $companyId = $this->currentCompanyId();
        if (!$companyId) {
            return redirect()->route('requester-management.departments.index')
                ->with('error', 'Debes seleccionar una entidad para crear departamentos.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        Department::create([
            'company_id' => $companyId,
            'name' => trim($validated['name']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($request->boolean('is_active', true)),
        ]);

        return redirect()->route('requester-management.departments.index')
            ->with('success', 'Departamento creado exitosamente.');
    }

    public function edit(Department $department)
    {
        $this->ensureWorkspace($department);

        return view('requester-management.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $this->ensureWorkspace($department);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->ignore($department->id)
                    ->where(fn ($query) => $query->where('company_id', $department->company_id)),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $department->update([
            'name' => trim($validated['name']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        return redirect()->route('requester-management.departments.index')
            ->with('success', 'Departamento actualizado exitosamente.');
    }

    public function destroy(Department $department)
    {
        $this->ensureWorkspace($department);

        $isInUse = \App\Models\Requester::withoutGlobalScopes()
            ->where('company_id', $department->company_id)
            ->where('department', $department->name)
            ->exists();

        if ($isInUse) {
            return redirect()->route('requester-management.departments.index')
                ->with('error', 'No se puede eliminar el departamento porque tiene solicitantes asociados.');
        }

        $department->delete();

        return redirect()->route('requester-management.departments.index')
            ->with('success', 'Departamento eliminado exitosamente.');
    }

    public function toggleStatus(Department $department)
    {
        $this->ensureWorkspace($department);

        $department->update([
            'is_active' => !$department->is_active,
        ]);

        return redirect()->route('requester-management.departments.index')
            ->with('success', 'Estado del departamento actualizado.');
    }
}
