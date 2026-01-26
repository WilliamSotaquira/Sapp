<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function select(Request $request): View
    {
        $user = $request->user();
        $companies = $user->companies()
            ->orderBy('name')
            ->get(['companies.id', 'companies.name']);

        return view('workspaces.select', [
            'companies' => $companies,
            'currentCompanyId' => $request->session()->get('current_company_id'),
        ]);
    }

    public function switch(Request $request): RedirectResponse
    {
        $user = $request->user();
        $companies = $user->companies()->get(['companies.id']);

        $request->validate([
            'company_id' => 'required|integer',
        ]);

        $companyId = (int) $request->input('company_id');
        if (!$companies->contains('id', $companyId)) {
            return back()->with('error', 'No tienes acceso a ese espacio de trabajo.');
        }

        $request->session()->put('current_company_id', $companyId);

        return redirect()->intended(route('dashboard'))->with('success', 'Espacio de trabajo actualizado.');
    }
}
