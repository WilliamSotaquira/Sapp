<?php

namespace App\Http\Controllers;

use App\Models\Contract;
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

        // IDs de las entidades a las que el usuario tiene acceso (usuario + técnico).
        $companyIds = $user->accessibleCompanyIds();

        // Solo se ofrece el contrato VIGENTE de cada entidad (su active_contract_id).
        // El histórico (contratos anteriores) ya no se "selecciona" para trabajar en él:
        // se consulta desde los listados y reportes, que muestran toda la entidad.
        $contracts = Contract::query()
            ->where('is_active', true)
            ->whereIn('company_id', $companyIds)
            ->whereIn('id', function ($q) {
                $q->select('active_contract_id')
                    ->from('companies')
                    ->whereNotNull('active_contract_id');
            })
            ->with('company:id,name,logo_path,primary_color,alternate_color')
            ->orderBy('company_id')
            ->orderBy('number')
            ->get(['id', 'company_id', 'number', 'name']);

        $contractsByCompany = $contracts->groupBy('company_id');

        return view('workspaces.select', [
            'contractsByCompany' => $contractsByCompany,
            'currentContractId' => $this->workspace->contractId(),
        ]);
    }

    public function switch(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'contract_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
        ]);

        $companyIds = $user->accessibleCompanyIds();
        $contractId = (int) $request->input('contract_id', 0);

        // Compatibilidad: si llega company_id (selectores legacy), resolver su contrato activo.
        if ($contractId <= 0 && $request->filled('company_id')) {
            $companyId = (int) $request->input('company_id');
            $contractId = (int) (Contract::where('company_id', $companyId)
                ->where('is_active', true)
                ->when(
                    \App\Models\Company::whereKey($companyId)->value('active_contract_id'),
                    fn ($q, $activeId) => $q->where('id', $activeId)
                )
                ->value('id')
                ?? Contract::where('company_id', $companyId)->where('is_active', true)->value('id')
                ?? 0);
        }

        // El contrato debe estar activo, pertenecer a una entidad del usuario y
        // ser el VIGENTE de esa entidad (no se trabaja dentro de contratos históricos).
        $contract = Contract::query()
            ->where('id', $contractId)
            ->where('is_active', true)
            ->whereIn('company_id', $companyIds)
            ->whereIn('id', function ($q) {
                $q->select('active_contract_id')
                    ->from('companies')
                    ->whereNotNull('active_contract_id');
            })
            ->first();

        if (!$contract) {
            return back()->with('error', 'No tienes acceso a ese contrato o no está activo.');
        }

        $this->workspace->switchToContract($contract->id);

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

            return redirect($redirectTo)->with('success', 'Contrato cambiado. Interpreta el texto de nuevo.');
        }

        return redirect()->intended(route('dashboard'))->with('success', 'Contrato activo actualizado.');
    }
}
