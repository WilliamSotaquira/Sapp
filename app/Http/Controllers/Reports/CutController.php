<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cut;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CutController extends Controller
{
    public function index(): View
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        $cuts = Cut::query()
            ->with('contract:id,number,name,company_id')
            ->withCount('serviceRequests')
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->when($currentCompany?->active_contract_id, function ($query) use ($currentCompany) {
                $query->where('contract_id', $currentCompany->active_contract_id);
            })
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('reports.cuts.index', compact('cuts'));
    }

    public function create(): View
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        $activeContract = $currentCompany?->activeContract;

        return view('reports.cuts.create', compact('activeContract', 'currentCompany'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        $activeContract = $currentCompany?->activeContract;
        if (!$activeContract) {
            return back()->withInput()->with('error', 'No hay contrato activo para el espacio de trabajo actual.');
        }

        $cut = Cut::create([
            ...$validated,
            'contract_id' => $activeContract->id,
            'created_by' => $request->user()?->id,
        ]);

        $this->syncCutServiceRequests($cut);

        return redirect()
            ->route('reports.cuts.show', $cut)
            ->with('success', 'Corte creado y solicitudes asociadas correctamente.');
    }

    public function show(Cut $cut): View
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }
        if ($currentCompany?->active_contract_id && (int) $cut->contract_id !== (int) $currentCompany->active_contract_id) {
            abort(403);
        }

        $serviceRequests = $cut->serviceRequests()
            ->with(['subService.service.family.contract', 'requester', 'assignee', 'sla'])
            ->orderByRaw("
                CASE service_requests.status
                    WHEN 'EN_PROCESO' THEN 1
                    WHEN 'ACEPTADA' THEN 2
                    WHEN 'PENDIENTE' THEN 3
                    WHEN 'PAUSADA' THEN 4
                    WHEN 'RESUELTA' THEN 5
                    WHEN 'CERRADA' THEN 6
                    WHEN 'CANCELADA' THEN 7
                    WHEN 'RECHAZADA' THEN 8
                    ELSE 9
                END
            ")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('reports.cuts.show', compact('cut', 'serviceRequests'));
    }

    public function update(Cut $cut, Request $request): RedirectResponse
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }
        if ($currentCompany?->active_contract_id && (int) $cut->contract_id !== (int) $currentCompany->active_contract_id) {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $cut->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        $this->syncCutServiceRequests($cut);

        return redirect()
            ->route('reports.cuts.show', $cut)
            ->with('success', 'Fechas del corte actualizadas y solicitudes sincronizadas correctamente.');
    }

    public function requests(Cut $cut, Request $request): View
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }
        if ($currentCompany?->active_contract_id && (int) $cut->contract_id !== (int) $currentCompany->active_contract_id) {
            abort(403);
        }

        $search = trim((string) $request->get('q', ''));

        $serviceRequestsQuery = ServiceRequest::query()
            ->with(['requester'])
            ->orderByDesc('created_at');
        if ($cut->contract_id) {
            $serviceRequestsQuery->whereHas('subService.service.family', function ($q) use ($cut) {
                $q->where('contract_id', $cut->contract_id);
            });
        }
        $currentCompanyId = (int) session('current_company_id');
        if ($currentCompanyId) {
            $serviceRequestsQuery->where('company_id', $currentCompanyId);
        }

        if ($search !== '') {
            $serviceRequestsQuery->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhereHas('requester', function ($r) use ($search) {
                        $r->where('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $serviceRequests = $serviceRequestsQuery->paginate(20);

        $selectedIds = $cut->serviceRequests()
            ->pluck('service_requests.id')
            ->all();

        return view('reports.cuts.requests', compact('cut', 'serviceRequests', 'selectedIds'));
    }

    public function updateRequests(Cut $cut, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_request_ids' => ['nullable', 'array'],
            'service_request_ids.*' => ['integer', 'exists:service_requests,id'],
        ]);

        $ids = $validated['service_request_ids'] ?? [];
        if (!empty($ids) && $cut->contract_id) {
            $validIds = ServiceRequest::query()
                ->whereIn('id', $ids)
                ->whereHas('subService.service.family', function ($q) use ($cut) {
                    $q->where('contract_id', $cut->contract_id);
                })
                ->pluck('id')
                ->all();
            if (count($validIds) !== count($ids)) {
                return back()->with('error', 'Algunas solicitudes no pertenecen al contrato de este corte.');
            }
        }

        $cut->serviceRequests()->sync($ids);

        return back()->with('success', 'Solicitudes asociadas al corte actualizadas correctamente.');
    }

    public function addRequestByTicket(Cut $cut, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_number' => ['required', 'string', 'max:255'],
        ]);

        $ticketNumber = trim($validated['ticket_number']);

        $serviceRequest = ServiceRequest::query()
            ->where('ticket_number', $ticketNumber)
            ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
            ->first();

        if (!$serviceRequest) {
            return back()->with('error', 'No se encontró una solicitud con ese ticket.');
        }
        if ($cut->contract_id) {
            $familyContractId = $serviceRequest->subService?->service?->family?->contract_id;
            if ((int) $familyContractId !== (int) $cut->contract_id) {
                return back()->with('error', 'La solicitud no pertenece al contrato de este corte.');
            }
        }

        $cut->serviceRequests()->syncWithoutDetaching([$serviceRequest->id]);

        return back()->with('success', 'Solicitud agregada al corte correctamente.');
    }

    public function removeRequest(Cut $cut, ServiceRequest $serviceRequest): RedirectResponse
    {
        $cut->serviceRequests()->detach($serviceRequest->id);

        return back()->with('success', 'Solicitud removida del corte correctamente.');
    }

    public function sync(Cut $cut): RedirectResponse
    {
        $this->syncCutServiceRequests($cut);

        return back()->with('success', 'Asociación actualizada según actividades del corte.');
    }

    public function exportPdf(Cut $cut)
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }
        if ($currentCompany?->active_contract_id && (int) $cut->contract_id !== (int) $currentCompany->active_contract_id) {
            abort(403);
        }

        $serviceRequests = $cut->serviceRequests()
            ->with(['subService.service.family.contract', 'requester', 'assignee', 'sla'])
            ->orderByDesc('created_at')
            ->get();

        $groupedData = $serviceRequests->groupBy(function ($request) {
            $family = $request->subService?->service?->family;
            $familyName = $family?->name ?? 'Sin Familia';
            $contractNumber = $family?->contract?->number;
            return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
        });

        $data = [
            'cut' => $cut,
            'serviceRequests' => $serviceRequests,
            'groupedData' => $groupedData,
            'generatedAt' => now(),
        ];

        $fileName = 'corte-' . $cut->id . '-' . now()->format('Y-m-d_His') . '.pdf';

        return Pdf::loadView('reports.cuts.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }

    private function syncCutServiceRequests(Cut $cut): void
    {
        [$start, $end] = $cut->getDateRangeForQuery();

        $requestIds = ServiceRequest::query()
            ->when((int) session('current_company_id'), fn($q) => $q->where('company_id', (int) session('current_company_id')))
            ->when($cut->contract_id, function ($q) use ($cut) {
                $q->whereHas('subService.service.family', function ($fq) use ($cut) {
                    $fq->where('contract_id', $cut->contract_id);
                });
            })
            ->where(function ($q) use ($start, $end) {
                // Actividad base: creación/actualización de la solicitud
                $q->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('updated_at', [$start, $end]);

                // Historiales de estado
                $q->orWhereHas('statusHistories', function ($h) use ($start, $end) {
                    $h->whereBetween('created_at', [$start, $end]);
                });

                // Evidencias
                $q->orWhereHas('evidences', function ($e) use ($start, $end) {
                    $e->whereBetween('created_at', [$start, $end]);
                });

                // Tareas y su historial (si aplica)
                $q->orWhereHas('tasks', function ($t) use ($start, $end) {
                    $t->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end])
                        ->orWhereHas('history', function ($th) use ($start, $end) {
                            $th->whereBetween('created_at', [$start, $end]);
                        });
                });
            })
            ->pluck('id')
            ->all();

        $cut->serviceRequests()->sync($requestIds);
    }
}
