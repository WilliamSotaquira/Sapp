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
        $companyId = $request->get('company_id') ?: $request->session()->get('current_company_id');

        $requesters = Requester::with(['company:id,name'])
            ->withCount('serviceRequests')
            ->when($search, function($query) use ($search) {
                return $query->search($search);
            })
            ->when($status !== 'all', function($query) use ($status) {
                return $query->where('is_active', $status === 'active');
            })
            ->when($companyId, function($query) use ($companyId) {
                return $query->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->paginate(20);

        return view('requester-management.requesters.index', compact('requesters', 'search', 'status', 'companyId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('requester-management.requesters.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->has('company_id')) {
            $request->merge(['company_id' => $request->session()->get('current_company_id')]);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:requesters,email',
            'phone' => 'nullable|string|max:20',
            'department' => ['nullable', 'string', 'max:255', Rule::in(Requester::getDepartmentOptions())],
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
            ->with(['subService.service.family', 'status'])
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

        return view('requester-management.requesters.edit', compact('requester', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requester $requester)
    {
        $this->ensureWorkspace($requester);

        if (!$request->has('company_id')) {
            $request->merge(['company_id' => $request->session()->get('current_company_id')]);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
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
                function ($attribute, $value, $fail) use ($requester) {
                    $value = is_string($value) ? trim($value) : $value;
                    if ($value === null || $value === '') {
                        return;
                    }
                    $allowed = Requester::getDepartmentOptions();
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
