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
        $cuts = Cut::query()
            ->withCount('serviceRequests')
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('reports.cuts.index', compact('cuts'));
    }

    public function create(): View
    {
        return view('reports.cuts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $cut = Cut::create([
            ...$validated,
            'created_by' => $request->user()?->id,
        ]);

        $this->syncCutServiceRequests($cut);

        return redirect()
            ->route('reports.cuts.show', $cut)
            ->with('success', 'Corte creado y solicitudes asociadas correctamente.');
    }

    public function show(Cut $cut): View
    {
        $serviceRequests = $cut->serviceRequests()
            ->with(['subService.service.family', 'requester', 'assignee', 'sla'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('reports.cuts.show', compact('cut', 'serviceRequests'));
    }

    public function requests(Cut $cut, Request $request): View
    {
        $search = trim((string) $request->get('q', ''));

        $serviceRequestsQuery = ServiceRequest::query()
            ->with(['requester'])
            ->orderByDesc('created_at');

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
            ->first();

        if (!$serviceRequest) {
            return back()->with('error', 'No se encontró una solicitud con ese ticket.');
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
        $serviceRequests = $cut->serviceRequests()
            ->with(['subService.service.family', 'requester', 'assignee', 'sla'])
            ->orderByDesc('created_at')
            ->get();

        $groupedData = $serviceRequests->groupBy(function ($request) {
            return $request->subService->service->family->name ?? 'Sin Familia';
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
