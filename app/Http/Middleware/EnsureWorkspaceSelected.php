<?php

namespace App\Http\Middleware;

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
        $companies = $user->companies()
            ->orderBy('name')
            ->get(['companies.id', 'companies.name', 'companies.active_contract_id']);

        $currentId = $this->workspace->id();

        // Validar que el workspace en sesión siga perteneciendo al usuario
        if ($currentId && !$companies->contains('id', $currentId)) {
            session()->forget('current_company_id');
            $this->workspace->reset();
            $currentId = null;
        }

        if (!$currentId) {
            if ($companies->count() === 1) {
                // Auto-seleccionar si solo tiene una entidad
                $currentId = $companies->first()->id;
                $this->workspace->switchTo($currentId);
            } else {
                // Redirigir a selección de workspace
                if (!$request->routeIs('workspaces.select', 'workspaces.switch', 'profile.*', 'logout', 'my-space.*')) {
                    return redirect()->route('workspaces.select');
                }
            }
        }

        // Compartir datos de workspace a todas las vistas
        $currentWorkspace = $currentId
            ? $this->workspace->company()
            : null;

        View::share('currentWorkspace', $currentWorkspace);
        View::share('userWorkspaces', $companies);

        return $next($request);
    }
}
