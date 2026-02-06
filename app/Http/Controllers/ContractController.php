<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $currentCompanyId = (int) session('current_company_id');

        $contracts = Contract::with(['company:id,name'])
            ->when($currentCompanyId, fn($q) => $q->where('company_id', $currentCompanyId))
            ->orderBy('number')
            ->get();

        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId ? Company::find($currentCompanyId) : null;
        $companies = Company::orderBy('name')->get();

        return view('contracts.create', compact('companies', 'currentCompany'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'number' => ['required', 'string', 'max:50', Rule::unique('contracts')->where('company_id', $request->input('company_id'))],
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $contract = Contract::create($validated);

        if ($validated['is_active']) {
            Contract::where('company_id', $validated['company_id'])
                ->where('id', '!=', $contract->id)
                ->update(['is_active' => false]);

            Company::where('id', $validated['company_id'])
                ->update(['active_contract_id' => $contract->id]);
        }

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato creado exitosamente.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['company:id,name', 'serviceFamilies:id,contract_id,name,code,is_active']);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId ? Company::find($currentCompanyId) : null;
        $companies = Company::orderBy('name')->get();

        return view('contracts.edit', compact('contract', 'companies', 'currentCompany'));
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('contracts')
                    ->where('company_id', $request->input('company_id'))
                    ->ignore($contract->id),
            ],
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = (bool)($request->is_active ?? false);

        $contract->update($validated);

        if ($validated['is_active']) {
            Contract::where('company_id', $validated['company_id'])
                ->where('id', '!=', $contract->id)
                ->update(['is_active' => false]);

            Company::where('id', $validated['company_id'])
                ->update(['active_contract_id' => $contract->id]);
        } else {
            $companyId = (int) $validated['company_id'];
            $activeId = (int) Company::where('id', $companyId)->value('active_contract_id');
            if ($activeId === (int) $contract->id) {
                $nextActive = Contract::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->where('id', '!=', $contract->id)
                    ->orderByDesc('id')
                    ->value('id');
                Company::where('id', $companyId)
                    ->update(['active_contract_id' => $nextActive ?: null]);
            }
        }

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato actualizado exitosamente.');
    }

    public function destroy(Contract $contract)
    {
        if ($contract->serviceFamilies()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar el contrato porque tiene familias asociadas.');
        }

        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato eliminado exitosamente.');
    }
}
