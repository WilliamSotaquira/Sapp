<?php

namespace App\Http\Controllers;

use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WorkspaceContext $workspace
    ) {}

    public function select(Request $request): View
    {
        $user = $request->user();
        $companies = $user->companies()
            ->with('activeContract:id,number,name')
            ->orderBy('name')
            ->get([
                'companies.id',
                'companies.name',
                'companies.logo_path',
                'companies.primary_color',
                'companies.alternate_color',
                'companies.active_contract_id',
            ]);

        return view('workspaces.select', [
            'companies' => $companies,
            'currentCompanyId' => $this->workspace->id(),
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
            return back()->with('error', 'No tienes acceso a esa entidad.');
        }

        $this->workspace->switchTo($companyId);

        // Si viene un redirect_to específico (ej: desde el intérprete de texto), usarlo
        $redirectTo = $request->input('redirect_to');
        if ($redirectTo && (str_starts_with($redirectTo, url('/')) || str_starts_with($redirectTo, '/'))) {
            // Preserve pasted text across workspace switch
            $preserveText = $request->input('preserve_text', '');
            if ($preserveText !== '') {
                session()->flash('_old_input', [
                    'plain_text_import_text' => $preserveText,
                    '__open_plain_text_import' => '1',
                ]);
            }

            return redirect($redirectTo)->with('success', 'Espacio de trabajo cambiado. Interpreta el texto de nuevo.');
        }

        return redirect()->intended(route('dashboard'))->with('success', 'Entidad activa actualizada.');
    }
}
