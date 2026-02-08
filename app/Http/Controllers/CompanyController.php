<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->withCount(['contracts', 'requesters', 'serviceRequests'])
            ->orderBy('name')
            ->paginate(15);

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        Company::create($validated);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Entidad creada exitosamente.');
    }

    public function show(Company $company)
    {
        $company->loadCount(['contracts', 'requesters', 'serviceRequests', 'users']);

        return view('companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate($this->rules($company->id));

        if ($request->boolean('remove_logo') && !empty($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
            $validated['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if (!empty($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($validated);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Entidad actualizada exitosamente.');
    }

    public function destroy(Company $company)
    {
        $company->loadCount(['contracts', 'requesters', 'serviceRequests', 'users']);

        if (
            $company->contracts_count > 0 ||
            $company->requesters_count > 0 ||
            $company->service_requests_count > 0 ||
            $company->users_count > 0
        ) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'No se puede eliminar la entidad porque tiene información relacionada.');
        }

        if (!empty($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Entidad eliminada exitosamente.');
    }

    private function rules(?int $companyId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'name')->ignore($companyId),
            ],
            'nit' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('companies', 'nit')->ignore($companyId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'alternate_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'contrast_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}
