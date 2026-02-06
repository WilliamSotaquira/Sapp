<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = $request->user();
        $companies = $user->companies()
            ->orderBy('name')
            ->get(['companies.id', 'companies.name', 'companies.active_contract_id']);

        $currentId = $request->session()->get('current_company_id');
        if ($currentId && !$companies->contains('id', $currentId)) {
            $request->session()->forget('current_company_id');
            $currentId = null;
        }

        if (!$currentId) {
            if ($companies->count() === 1) {
                $currentId = $companies->first()->id;
                $request->session()->put('current_company_id', $currentId);
            } else {
                if (!$request->routeIs('workspaces.select', 'workspaces.switch', 'profile.*', 'logout')) {
                    return redirect()->route('workspaces.select');
                }
            }
        }

        $currentWorkspace = $currentId
            ? \App\Models\Company::with('activeContract')->find($currentId)
            : null;
        View::share('currentWorkspace', $currentWorkspace);
        View::share('userWorkspaces', $companies);

        return $next($request);
    }
}
