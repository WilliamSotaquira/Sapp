<?php
// app/Http/Controllers/RequesterManagementController.php

namespace App\Http\Controllers;

use App\Models\Requester;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequesterManagementController extends Controller
{
    private function ensureWorkspace(Requester $requester): void
    {
        $companyId = session('current_company_id');
        if ($companyId && (int) $requester->company_id !== (int) $companyId) {
            abort(403, 'No tienes acceso a este solicitante.');
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'active');
        $department = $request->get('department');
        $position = $request->get('position');
        $hasRequests = $request->get('has_requests');
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $companyId = $request->session()->get('current_company_id');

        $allowedSorts = [
            'name',
            'department',
            'position',
            'service_requests_count',
            'is_active',
        ];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $requesters = Requester::with(['company:id,name'])
            ->withCount('serviceRequests')
            ->when($search, function($query) use ($search) {
                return $query->search($search);
            })
            ->when($status !== 'all', function($query) use ($status) {
                return $query->where('is_active', $status === 'active');
            })
            ->when($department, function ($query) use ($department) {
                return $query->where('department', 'like', '%' . $department . '%');
            })
            ->when($position, function ($query) use ($position) {
                return $query->where('position', 'like', '%' . $position . '%');
            })
            ->when($hasRequests === 'yes', function ($query) {
                return $query->has('serviceRequests');
            })
            ->when($hasRequests === 'no', function ($query) {
                return $query->doesntHave('serviceRequests');
            })
            ->when($companyId, function($query) use ($companyId) {
                return $query->where('company_id', $companyId);
            })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('requester-management.requesters.index', compact(
            'requesters',
            'search',
            'status',
            'department',
            'position',
            'hasRequests',
            'sortBy',
            'sortDir',
            'companyId'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);
        $currentCompanyId = (int) session('current_company_id');
        $departmentOptions = Requester::getDepartmentOptions($currentCompanyId);

        return view('requester-management.requesters.create', compact('companies', 'departmentOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentCompanyId = $request->session()->get('current_company_id');
        if ($currentCompanyId) {
            $request->merge(['company_id' => $currentCompanyId]);
        }

        $companyRules = ['required', 'exists:companies,id'];
        if ($currentCompanyId) {
            $companyRules[] = Rule::in([$currentCompanyId]);
        }

        $validated = $request->validate([
            'company_id' => $companyRules,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:requesters,email',
            'phone' => 'nullable|string|max:20',
            'department' => ['nullable', 'string', 'max:255', Rule::in(Requester::getDepartmentOptions((int) $currentCompanyId))],
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Requester::create($validated);

        return redirect()->route('requester-management.requesters.index')
            ->with('success', 'Solicitante creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Requester $requester)
    {
        $this->ensureWorkspace($requester);

        $serviceRequests = $requester->serviceRequests()
            ->with(['subService.service.family'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('requester-management.requesters.show', compact('requester', 'serviceRequests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requester $requester)
    {
        $this->ensureWorkspace($requester);

        $companies = Company::orderBy('name')->get(['id', 'name']);
        $currentCompanyId = (int) session('current_company_id');
        $departmentOptions = Requester::getDepartmentOptions($currentCompanyId);

        return view('requester-management.requesters.edit', compact('requester', 'companies', 'departmentOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requester $requester)
    {
        $this->ensureWorkspace($requester);

        $currentCompanyId = $request->session()->get('current_company_id');
        if ($currentCompanyId) {
            $request->merge(['company_id' => $currentCompanyId]);
        }

        $companyRules = ['required', 'exists:companies,id'];
        if ($currentCompanyId) {
            $companyRules[] = Rule::in([$currentCompanyId]);
        }

        $validated = $request->validate([
            'company_id' => $companyRules,
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('requesters')->ignore($requester->id),
            ],
            'phone' => 'nullable|string|max:20',
            'department' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($requester, $currentCompanyId) {
                    $value = is_string($value) ? trim($value) : $value;
                    if ($value === null || $value === '') {
                        return;
                    }

                    $allowed = Requester::getDepartmentOptions((int) $currentCompanyId);
                    if (!in_array($value, $allowed, true) && $value !== $requester->department) {
                        $fail('El departamento seleccionado no es válido.');
                    }
                },
            ],
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $requester->update($validated);

        return redirect()->route('requester-management.requesters.index')
            ->with('success', 'Solicitante actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requester $requester)
    {
        $this->ensureWorkspace($requester);

        if ($requester->serviceRequests()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar el solicitante porque tiene solicitudes asociadas.');
        }

        $requester->delete();

        return redirect()->route('requester-management.requesters.index')
            ->with('success', 'Solicitante eliminado exitosamente.');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Requester $requester)
    {
        $this->ensureWorkspace($requester);

        $requester->update([
            'is_active' => !$requester->is_active
        ]);

        $status = $requester->is_active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Solicitante {$status} exitosamente.");
    }
}
