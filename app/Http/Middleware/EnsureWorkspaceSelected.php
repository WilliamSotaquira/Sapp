<?php

namespace App\Http\Middleware;

use App\Models\Contract;
use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSelected
{
    public function __construct(
        protected WorkspaceContext $workspace
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = $request->user();
        $companyIds = $user->companies()->pluck('companies.id');

        // Contratos activos de las entidades del usuario (unidad de trabajo).
        $contracts = Contract::query()
            ->where('is_active', true)
            ->whereIn('company_id', $companyIds)
            ->with('company:id,name,active_contract_id,primary_color,alternate_color,contrast_color,logo_path')
            ->orderBy('company_id')
            ->orderBy('number')
            ->get(['id', 'company_id', 'number', 'name']);

        $currentContractId = $this->workspace->contractId();

        // Validar que el contrato activo siga siendo accesible por el usuario.
        if ($currentContractId && !$contracts->contains('id', $currentContractId)) {
            session()->forget(['current_contract_id', 'current_company_id']);
            $this->workspace->reset();
            $currentContractId = null;
        }

        if (!$currentContractId) {
            if ($contracts->count() === 1) {
                // Auto-seleccionar si solo hay un contrato disponible.
                $currentContractId = $contracts->first()->id;
                $this->workspace->switchToContract($currentContractId);
            } else {
                // Redirigir a selección de contrato.
                if (!$request->routeIs('workspaces.select', 'workspaces.switch', 'profile.*', 'logout', 'my-space.*')) {
                    return redirect()->route('workspaces.select');
                }
            }
        }

        // Contrato activo y su entidad (para theming, personas, catálogo).
        $currentContract = $currentContractId
            ? $contracts->firstWhere('id', $currentContractId)
            : null;

        // $currentWorkspace se mantiene como la Company (compatibilidad con vistas/theming existentes),
        // derivada del contrato activo.
        $currentWorkspace = $currentContract?->company;

        View::share('currentContract', $currentContract);
        View::share('currentWorkspace', $currentWorkspace);
        View::share('userContracts', $contracts);
        // Compatibilidad: algunas vistas aún usan $userWorkspaces (lista de entidades).
        View::share('userWorkspaces', $contracts->pluck('company')->unique('id')->values());

        return $next($request);
    }
}
