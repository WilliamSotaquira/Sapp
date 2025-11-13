<?php
// app/Http/Controllers/RequesterManagementController.php

namespace App\Http\Controllers;

use App\Models\Requester;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequesterManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'active');

        $requesters = Requester::withCount('serviceRequests')
            ->when($search, function($query) use ($search) {
                return $query->search($search);
            })
            ->when($status !== 'all', function($query) use ($status) {
                return $query->where('is_active', $status === 'active');
            })
            ->orderBy('name')
            ->paginate(20);

        return view('requester-management.requesters.index', compact('requesters', 'search', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('requester-management.requesters.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:requesters,email',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
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
        return view('requester-management.requesters.edit', compact('requester'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requester $requester)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('requesters')->ignore($requester->id),
            ],
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
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
        $requester->update([
            'is_active' => !$requester->is_active
        ]);

        $status = $requester->is_active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Solicitante {$status} exitosamente.");
    }
}
