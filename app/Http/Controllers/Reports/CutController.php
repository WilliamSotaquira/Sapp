<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cut;
use App\Models\ServiceFamily;
use App\Models\ServiceRequest;
use App\Services\DateSuggestionService;
use App\Services\EvidenceOrganizerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use ZipArchive;

class CutController extends Controller
{
    public function __construct(
        private readonly DateSuggestionService $dateSuggestionService,
        private readonly EvidenceOrganizerService $evidenceOrganizerService,
    ) {
    }
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

        $dateSuggestion = null;
        $suggestedFolderPath = null;
        $hasActiveContract = $activeContract !== null;
        $currentOpenCut = null;

        if ($hasActiveContract) {
            $dateSuggestion = $this->dateSuggestionService->suggestDates($activeContract->id);

            $currentOpenCut = Cut::query()
                ->where('contract_id', $activeContract->id)
                ->open()
                ->withCount('serviceRequests')
                ->first();

            // Use a placeholder cut ID (next auto-increment or 0) for folder suggestion
            $nextCutId = (int) DB::table('cuts')->max('id') + 1;
            $suggestedFolderPath = $this->evidenceOrganizerService->suggestFolderPath(
                $nextCutId,
                $dateSuggestion->startDate,
                $activeContract->number
            );
        }

        return view('reports.cuts.create', compact(
            'activeContract',
            'currentCompany',
            'dateSuggestion',
            'suggestedFolderPath',
            'hasActiveContract',
            'currentOpenCut'
        ));
    }

    public function edit(Cut $cut): View
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

        return view('reports.cuts.edit', compact('cut', 'currentCompany'));
    }

    /**
     * AJAX preview: estimate how many requests would be captured by a date range.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        $activeContract = $currentCompany?->activeContract;

        if (!$activeContract) {
            return response()->json(['error' => 'Sin contrato activo'], 422);
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // If end_date has no time, assume end of day
        if ($endDate->format('H:i:s') === '00:00:00' && $endDate->gt($startDate)) {
            $endDate = $endDate->copy()->setTime(23, 59, 59);
        }

        $durationDays = (int) $startDate->diffInDays($endDate);
        $durationHours = $startDate->diffInHours($endDate);

        // Count eligible requests that would fall in this range
        $requestCount = ServiceRequest::query()
            ->eligibleForCutAssignment()
            ->whereHas('subService.service.family', function ($fq) use ($activeContract) {
                $fq->where('contract_id', $activeContract->id);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$startDate, $endDate]);
            })
            ->count();

        // Check overlap
        $overlapCheck = $this->dateSuggestionService->validateNoOverlap(
            $activeContract->id,
            $startDate,
            $endDate
        );

        $warnings = [];

        if ($durationHours < 12) {
            $warnings[] = 'El rango es menor a 12 horas. No se permitirá crear el corte.';
        } elseif ($durationDays < 7) {
            $warnings[] = "El rango es de solo {$durationDays} día(s). Los cortes normalmente cubren periodos de 2-4 semanas.";
        }

        if ($requestCount === 0) {
            $warnings[] = 'No hay solicitudes cerradas/resueltas en este rango. El corte quedará vacío.';
        }

        if ($overlapCheck->hasOverlap) {
            $conflicting = $overlapCheck->conflictingCut;
            $warnings[] = "Se solapa con el corte \"{$conflicting->name}\" ({$conflicting->start_date->format('d/m/Y')} - {$conflicting->end_date->format('d/m/Y')}).";
        }

        return response()->json([
            'request_count' => $requestCount,
            'duration_days' => $durationDays,
            'warnings' => $warnings,
            'valid' => $durationHours >= 12 && !$overlapCheck->hasOverlap,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'folder_name' => ['nullable', 'string', 'max:128'],
        ]);

        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;
        $activeContract = $currentCompany?->activeContract;
        if (!$activeContract) {
            return back()->withInput()->with('error', 'No hay contrato activo para el espacio de trabajo actual.');
        }

        // Validate date overlap using DateSuggestionService
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Ensure end_date has time component set to end of day if it equals start of day
        // This prevents accidentally creating cuts with ranges of a few hours
        if ($endDate->format('H:i:s') === '00:00:00' && $endDate->gt($startDate)) {
            $endDate = $endDate->copy()->setTime(23, 59, 59);
            $validated['end_date'] = $endDate->toDateTimeString();
        }

        // Validate minimum cut duration (at least 1 day)
        if ($startDate->diffInHours($endDate) < 12) {
            return back()->withInput()->withErrors([
                'end_date' => 'El corte debe tener una duración mínima de al menos 12 horas. Verifica las fechas.',
            ]);
        }

        // Auto-close previous cut: set its end_date to 1 second before this cut's start_date.
        // The definitive end of a cut is determined by when the next cut begins.
        $previousCut = Cut::query()
            ->where('contract_id', $activeContract->id)
            ->where('start_date', '<', $startDate)
            ->orderByDesc('start_date')
            ->first();

        if ($previousCut) {
            $definitiveEnd = $startDate->copy()->subSecond();
            if (!$previousCut->end_date->eq($definitiveEnd)) {
                $previousCut->update(['end_date' => $definitiveEnd]);
                $this->syncCutServiceRequests($previousCut);
            }
        }

        // Validate no overlap with existing cuts of the same contract
        // Exclude the previous cut since it was just auto-closed
        $overlapCheck = $this->dateSuggestionService->validateNoOverlap(
            $activeContract->id,
            $startDate,
            $endDate,
            $previousCut?->id
        );
        if ($overlapCheck->hasOverlap) {
            $conflicting = $overlapCheck->conflictingCut;
            return back()->withInput()->withErrors([
                'start_date' => "Las fechas se solapan con el corte \"{$conflicting->name}\" ({$conflicting->start_date->format('Y-m-d')} a {$conflicting->end_date->format('Y-m-d')}). Ajusta las fechas o edita el corte existente.",
            ]);
        }

        // Handle folder creation
        $folderPath = null;
        $folderName = trim($validated['folder_name'] ?? '');
        $basePath = $this->evidenceOrganizerService->resolveBasePath();

        if ($folderName !== '') {
            // Validate custom folder name
            $folderValidation = $this->evidenceOrganizerService->validateFolderName($folderName, $basePath);
            if (!$folderValidation->passed) {
                return back()->withInput()->withErrors(['folder_name' => $folderValidation->errors]);
            }
            $folderPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $folderName;
        }

        // Create the cut record first to get the ID
        $cut = Cut::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'notes' => $validated['notes'] ?? null,
            'contract_id' => $activeContract->id,
            'created_by' => $request->user()?->id,
        ]);

        // If no custom folder name provided, generate default folder path using new cut ID
        if ($folderPath === null) {
            $folderPath = $this->evidenceOrganizerService->suggestFolderPath($cut->id, $startDate, $activeContract->number);
        }

        // Create the folder on the filesystem
        if (!$this->evidenceOrganizerService->createCutFolder($folderPath)) {
            // Folder creation failed: delete the cut record and show error
            $cut->delete();
            return back()->withInput()->with('error', 'No se pudo crear la carpeta del corte. Verifica los permisos del directorio.');
        }

        // Store folder_path in the cut record
        $cut->update(['folder_path' => $folderPath]);

        $this->syncCutServiceRequests($cut);

        return redirect()
            ->route('reports.cuts.show', $cut)
            ->with('success', 'Corte creado y solicitudes asociadas correctamente.');
    }

    public function show(Cut $cut, Request $request): View|JsonResponse
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

        $families = ServiceFamily::query()
            ->where(function ($q) use ($cut) {
                // Familias activas del contrato
                $q->where('is_active', true);
                // O familias inactivas que tengan solicitudes en este corte (histórico)
                $q->orWhereIn('service_families.id', function ($sub) use ($cut) {
                    $sub->select('services.service_family_id')
                        ->from('services')
                        ->join('sub_services', 'sub_services.service_id', '=', 'services.id')
                        ->join('service_requests', 'service_requests.sub_service_id', '=', 'sub_services.id')
                        ->join('cut_service_request', 'cut_service_request.service_request_id', '=', 'service_requests.id')
                        ->where('cut_service_request.cut_id', $cut->id);
                });
            })
            ->when($cut->contract_id, fn($q) => $q->where('contract_id', $cut->contract_id))
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->with('contract:id,number')
            ->withCount('services')
            ->ordered()
            ->get();

        $selectedFamilyIds = collect($request->input('families', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedFamilyIds)) {
            $validFamilyIds = $families->pluck('id')->all();
            $selectedFamilyIds = array_values(array_intersect($selectedFamilyIds, $validFamilyIds));
        }
        $selectedFamilyLabels = $families
            ->whereIn('id', $selectedFamilyIds)
            ->map(fn($family) => $this->formatFamilyLabel($family))
            ->values();

        $familyRequestCounts = $cut->serviceRequests()
            ->selectRaw('services.service_family_id as family_id, COUNT(DISTINCT service_requests.id) as total')
            ->join('sub_services', 'service_requests.sub_service_id', '=', 'sub_services.id')
            ->join('services', 'sub_services.service_id', '=', 'services.id')
            ->groupBy('services.service_family_id')
            ->pluck('total', 'family_id');

        $serviceRequests = $cut->serviceRequests()
            ->with(['subService.service.family.contract', 'requester', 'assignee', 'sla'])
            ->when(empty($selectedFamilyIds), function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when(!empty($selectedFamilyIds), function ($query) use ($selectedFamilyIds) {
                $query->whereHas('subService.service.family', function ($q) use ($selectedFamilyIds) {
                    $q->whereIn('service_families.id', $selectedFamilyIds);
                });
            })
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
            ->orderByDesc('technician_assigned_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends([
                'families' => $selectedFamilyIds,
                'format' => (string) $request->input('format', 'pdf'),
            ]);

        if ($request->ajax()) {
            $tableHtml = view('reports.cuts.partials.service-requests-table', [
                'cut' => $cut,
                'serviceRequests' => $serviceRequests,
                'selectedFamilyIds' => $selectedFamilyIds,
                'selectedFamilyLabels' => $selectedFamilyLabels,
            ])->render();

            return response()->json([
                'html' => $tableHtml,
                'url' => route('reports.cuts.show', [
                    'cut' => $cut,
                    'families' => $selectedFamilyIds,
                    'format' => (string) $request->input('format', 'pdf'),
                    'page' => $serviceRequests->currentPage(),
                ]),
            ]);
        }

        // Load evidences for the organize section (only when cut has a folder_path)
        $evidences = collect();
        $evidenceCount = 0;
        if (!empty($cut->folder_path)) {
            $evidences = \App\Models\ServiceRequestEvidence::query()
                ->whereIn('service_request_id', $cut->serviceRequests()->pluck('service_requests.id'))
                ->with('serviceRequest:id,ticket_number')
                ->orderBy('created_at', 'desc')
                ->get();
            $evidenceCount = $evidences->count();
        }

        return view('reports.cuts.show', compact(
            'cut',
            'serviceRequests',
            'families',
            'selectedFamilyIds',
            'selectedFamilyLabels',
            'familyRequestCounts',
            'evidences',
            'evidenceCount'
        ));
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
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'folder_path' => ['nullable', 'string', 'max:500'],
        ]);

        // Auto-adjust adjacent cuts to maintain contiguity
        $newStart = Carbon::parse($validated['start_date']);
        $newEnd = Carbon::parse($validated['end_date']);

        // Ensure end_date has time component set to end of day if it equals start of day
        if ($newEnd->format('H:i:s') === '00:00:00' && $newEnd->gt($newStart)) {
            $newEnd = $newEnd->copy()->setTime(23, 59, 59);
            $validated['end_date'] = $newEnd->toDateTimeString();
        }

        // Validate minimum cut duration (at least 12 hours)
        if ($newStart->diffInHours($newEnd) < 12) {
            return back()->withInput()->withErrors([
                'end_date' => 'El corte debe tener una duración mínima de al menos 12 horas. Verifica las fechas.',
            ]);
        }

        // Validate no overlap with existing cuts of the same contract (excluding this cut)
        $overlapCheck = $this->dateSuggestionService->validateNoOverlap(
            $cut->contract_id,
            $newStart,
            $newEnd,
            $cut->id
        );
        if ($overlapCheck->hasOverlap) {
            $conflicting = $overlapCheck->conflictingCut;
            return back()->withInput()->withErrors([
                'start_date' => "Las fechas se solapan con el corte \"{$conflicting->name}\" ({$conflicting->start_date->format('Y-m-d')} a {$conflicting->end_date->format('Y-m-d')}). Ajusta las fechas.",
            ]);
        }

        // Auto-close previous cut: set its end_date to 1 second before this cut's start_date
        $previousCut = Cut::query()
            ->where('contract_id', $cut->contract_id)
            ->where('id', '!=', $cut->id)
            ->where('start_date', '<', $newStart)
            ->orderByDesc('start_date')
            ->first();

        if ($previousCut) {
            $definitiveEnd = $newStart->copy()->subSecond();
            if (!$previousCut->end_date->eq($definitiveEnd)) {
                $previousCut->update(['end_date' => $definitiveEnd]);
                $this->syncCutServiceRequests($previousCut);
            }
        }

        // Auto-adjust next cut: set its start_date to 1 second after this cut's end_date
        $nextCut = Cut::query()
            ->where('contract_id', $cut->contract_id)
            ->where('id', '!=', $cut->id)
            ->where('start_date', '>', $newStart)
            ->orderBy('start_date')
            ->first();

        if ($nextCut) {
            $expectedNextStart = $newEnd->copy()->addSecond();
            if (!$nextCut->start_date->eq($expectedNextStart)) {
                $nextCut->update(['start_date' => $expectedNextStart]);
                $this->syncCutServiceRequests($nextCut);
            }
        }

        // Handle folder_path: create directory if provided and doesn't exist
        $folderPath = trim($validated['folder_path'] ?? '');
        if ($folderPath !== '') {
            if (!is_dir($folderPath)) {
                if (!$this->evidenceOrganizerService->createCutFolder($folderPath)) {
                    return back()->withInput()->withErrors(['folder_path' => 'No se pudo crear la carpeta. Verifica que la ruta sea válida y que la aplicación tenga permisos de escritura.']);
                }
            }
        }

        $cut->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'notes' => $validated['notes'] ?? null,
            'folder_path' => $folderPath ?: null,
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
        [$start, $end] = $cut->getDateRangeForQuery();

        $serviceRequestsQuery = ServiceRequest::query()
            ->with(['requester'])
            ->eligibleForCutAssignment();

        // Open cuts: no end_date limit
        if ($cut->isOpen()) {
            $serviceRequestsQuery->where(function ($q) use ($start) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) >= ?', [$start]);
            });
        } else {
            $serviceRequestsQuery->where(function ($q) use ($start, $end) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
            });
        }

        $serviceRequestsQuery
            ->orderByRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) DESC')
            ->orderByDesc('created_at');
        if ($cut->contract_id) {
            $serviceRequestsQuery->whereHas('subService.service.family', function ($q) use ($cut) {
                $q->where('contract_id', $cut->contract_id);
            });
        }
        // ServiceRequest ya scopea por contrato activo (global scope 'workspace').

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

    public function associatedRequests(Cut $cut, Request $request): View
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

        $search = trim((string) $request->query('q', ''));
        $familyId = (int) $request->query('family_id', 0);

        $family = null;
        if ($familyId > 0) {
            $family = ServiceFamily::query()
                ->with('contract:id,number')
                ->when($cut->contract_id, fn($q) => $q->where('contract_id', $cut->contract_id))
                ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                    $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                        $q->where('company_id', $currentCompanyId);
                    });
                })
                ->find($familyId);

            if (!$family) {
                abort(404);
            }
        }

        $serviceRequestsQuery = $cut->serviceRequests()
            ->with(['subService.service.family.contract', 'requester', 'assignee'])
            ->when($familyId > 0, function ($query) use ($familyId) {
                $query->whereHas('subService.service.family', function ($q) use ($familyId) {
                    $q->where('service_families.id', $familyId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhereHas('requester', function ($rq) use ($search) {
                            $rq->where('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('created_at');

        $totalAssociated = (clone $serviceRequestsQuery)->count();
        $serviceRequests = $serviceRequestsQuery
            ->paginate(20)
            ->withQueryString();

        return view('reports.cuts.associated-requests', compact(
            'cut',
            'serviceRequests',
            'family',
            'familyId',
            'totalAssociated',
            'search'
        ));
    }

    public function updateRequests(Cut $cut, Request $request): RedirectResponse
    {
        $this->syncCutServiceRequests($cut);

        return back()->with('success', 'Solicitudes recalculadas según la fecha de cierre/resolución.');
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
        if ($cut->contract_id) {
            $familyContractId = $serviceRequest->subService?->service?->family?->contract_id;
            if ((int) $familyContractId !== (int) $cut->contract_id) {
                return back()->with('error', 'La solicitud no pertenece al contrato de este corte.');
            }
        }
        if (!$serviceRequest->canBeAssociatedToCut()) {
            return back()->with('error', 'La solicitud debe estar RESUELTA o CERRADA para asociarse a un corte.');
        }
        $cutRef = $serviceRequest->getCutReferenceAt();
        if (!$cutRef || !$cut->containsDate($cutRef)) {
            return back()->with('error', 'La fecha de cierre/resolución de la solicitud no pertenece al rango de este corte.');
        }

        $this->syncCutServiceRequests($cut);

        return back()->with('success', 'La solicitud pertenece al rango del corte y la asociación fue recalculada.');
    }

    public function removeRequest(Cut $cut, ServiceRequest $serviceRequest): RedirectResponse
    {
        return back()->with(
            'error',
            'La asociación del corte se calcula por la fecha de cierre/resolución de la solicitud. Ajusta esa fecha o el rango del corte para removerla.'
        );
    }

    public function sync(Cut $cut): RedirectResponse
    {
        $this->syncCutServiceRequests($cut);

        return back()->with('success', 'Asociación actualizada según la fecha de cierre/resolución.');
    }

    /**
     * Close the current cut and automatically create the next open cut.
     */
    public function close(Cut $cut, Request $request): RedirectResponse
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

        if ($cut->isClosed()) {
            return back()->with('error', 'Este corte ya está cerrado.');
        }

        // Don't close an empty cut — suggest deleting instead
        $requestCount = $cut->serviceRequests()->count();
        if ($requestCount === 0) {
            return back()->with('error', 'No se puede cerrar un corte sin solicitudes. Elimínalo o espera a que tenga actividad.');
        }

        // Determine close date: use provided date or now
        $closeAt = $request->filled('close_at')
            ? Carbon::parse($request->input('close_at'))
            : now();

        // Ensure close date is not before the cut's start date
        if ($closeAt->lt($cut->start_date)) {
            return back()->with('error', 'La fecha de cierre no puede ser anterior al inicio del corte.');
        }

        // Sync current cut's requests before closing
        $this->syncCutServiceRequests($cut);

        // Close and create next
        $nextCut = $cut->closeAndCreateNext($closeAt);

        // Generate folder for the new cut using cut name
        $activeContract = $currentCompany?->activeContract;
        if ($activeContract) {
            $basePath = $this->evidenceOrganizerService->resolveBasePath();
            $sanitizedContract = preg_replace('/[\\\\\/:"*?<>|]+/', '-', $activeContract->number ?? '');
            $sanitizedCutName = preg_replace('/[\\\\\/:"*?<>|]+/', '-', $nextCut->name);
            $folderPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR
                . $sanitizedContract . DIRECTORY_SEPARATOR
                . $sanitizedCutName;

            if ($this->evidenceOrganizerService->createCutFolder($folderPath)) {
                $nextCut->update(['folder_path' => $folderPath]);
            }
        }

        // Sync the new cut's requests
        $this->syncCutServiceRequests($nextCut);

        return redirect()
            ->route('reports.cuts.show', $nextCut)
            ->with('success', "Corte \"{$cut->name}\" cerrado. Nuevo corte \"{$nextCut->name}\" creado automáticamente.");
    }

    /**
     * Delete a cut. Only allowed if the cut has no associated service requests.
     */
    public function destroy(Cut $cut): RedirectResponse
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? \App\Models\Company::with('activeContract')->find($currentCompanyId)
            : null;

        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }

        // Only allow deleting cuts with no service requests
        if ($cut->serviceRequests()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un corte que tiene solicitudes asociadas. Desasócialas primero.');
        }

        $cutName = $cut->name;
        $contractId = $cut->contract_id;
        $wasOpen = $cut->isOpen();

        // If deleting the open cut, reopen the previous one
        if ($wasOpen) {
            $previousCut = Cut::query()
                ->where('contract_id', $contractId)
                ->where('id', '!=', $cut->id)
                ->orderByDesc('start_date')
                ->first();

            if ($previousCut) {
                $previousCut->update([
                    'status' => Cut::STATUS_OPEN,
                    'closed_at' => null,
                    'end_date' => now()->addDays(30)->setTime(23, 59, 59),
                ]);
            }
        }

        $cut->serviceRequests()->detach();
        $cut->delete();

        return redirect()
            ->route('reports.cuts.index')
            ->with('success', "Corte \"{$cutName}\" eliminado.");
    }

    public function export(Request $request, Cut $cut)
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
            'format' => ['nullable', 'in:pdf,zip'],
            'families' => ['required', 'array', 'min:1'],
            'families.*' => ['integer', 'exists:service_families,id'],
            'check_only' => ['nullable', 'boolean'],
        ]);
        $companyId = (int) ($cut->contract?->company_id ?: session('current_company_id'));
        $generatedBy = $request->user()?->name ?? 'Sistema';
        $generatedByEmail = $request->user()?->getEmailForCompany($companyId) ?: $request->user()?->email;
        $generatedByDependency = $request->user()?->getPositionForCompany($companyId);

        $families = ServiceFamily::query()
            ->where(function ($q) use ($cut) {
                $q->where('is_active', true);
                $q->orWhereIn('service_families.id', function ($sub) use ($cut) {
                    $sub->select('services.service_family_id')
                        ->from('services')
                        ->join('sub_services', 'sub_services.service_id', '=', 'services.id')
                        ->join('service_requests', 'service_requests.sub_service_id', '=', 'sub_services.id')
                        ->join('cut_service_request', 'cut_service_request.service_request_id', '=', 'service_requests.id')
                        ->where('cut_service_request.cut_id', $cut->id);
                });
            })
            ->when($cut->contract_id, fn($q) => $q->where('contract_id', $cut->contract_id))
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->with('contract:id,number')
            ->ordered()
            ->get();

        $selectedFamilyIds = collect($validated['families'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedFamilyIds)) {
            $validFamilyIds = $families->pluck('id')->all();
            $selectedFamilyIds = array_values(array_intersect($selectedFamilyIds, $validFamilyIds));
        }

        $selectedFamilyLabels = $families
            ->whereIn('id', $selectedFamilyIds)
            ->map(function ($family) {
                return $this->formatFamilyLabel($family);
            })
            ->values();

        $serviceRequests = $cut->serviceRequests()
            ->with(['subService.service.family.contract', 'requester', 'assignee', 'sla', 'tasks.subtasks', 'evidences.uploadedBy'])
            ->when(!empty($selectedFamilyIds), function ($query) use ($selectedFamilyIds) {
                $query->whereHas('subService.service.family', function ($q) use ($selectedFamilyIds) {
                    $q->whereIn('service_families.id', $selectedFamilyIds);
                });
            })
            ->orderByDesc('technician_assigned_at')
            ->orderByDesc('created_at')
            ->get();

        $evidences = $serviceRequests
            ->flatMap(fn($serviceRequest) => $serviceRequest->evidences ?? collect())
            ->sortByDesc('created_at')
            ->values();

        if ((bool) ($validated['check_only'] ?? false)) {
            $availableFamilyIds = $serviceRequests
                ->map(fn($sr) => (int) ($sr->subService?->service?->family?->id ?? 0))
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            return response()->json([
                'has_requests' => !empty($availableFamilyIds),
                'available_family_ids' => $availableFamilyIds,
            ]);
        }

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
            'generatedBy' => $generatedBy,
            'generatedByEmail' => $generatedByEmail,
            'generatedByDependency' => $generatedByDependency,
            'selectedFamilyLabels' => $selectedFamilyLabels,
            'evidences' => $evidences,
        ];

        $format = $validated['format'] ?? 'pdf';
        $timestamp = now()->format('Y-m-d_His');
        $baseFileName = 'corte-' . $cut->id;
        if (count($selectedFamilyIds) === 1) {
            $singleLabel = (string) ($selectedFamilyLabels->first() ?? 'familia');
            $familySlug = Str::slug($singleLabel, '-');
            if ($familySlug !== '') {
                $baseFileName .= '-' . $familySlug;
            }
        }
        $baseFileName .= '-' . $timestamp;

        if ($format === 'zip') {
            return $this->generateZipWithEvidences($data, $baseFileName);
        }

        return $this->generateFamilyPdfPackage(
            $cut,
            $serviceRequests,
            $families,
            $selectedFamilyIds,
            $baseFileName,
            $generatedBy,
            (string) ($generatedByEmail ?? ''),
            (string) ($generatedByDependency ?? '')
        );
    }

    public function exportPdf(Cut $cut, Request $request)
    {
        $request->merge(['format' => 'pdf']);
        return $this->export($request, $cut);
    }

    /**
     * Organize selected evidence files into the cut's folder structure.
     *
     * Accepts a batch of evidence IDs (max 50), validates the cut has a folder_path,
     * delegates to EvidenceOrganizerService, and returns a summary with success/failure counts.
     */
    public function organizeEvidences(Request $request, Cut $cut): RedirectResponse
    {
        $validated = $request->validate([
            'evidence_ids' => ['required', 'array', 'max:50'],
            'evidence_ids.*' => ['integer'],
        ]);

        // Validate that the cut has a folder_path configured
        if (empty($cut->folder_path)) {
            return back()->with('error', 'El corte no tiene una carpeta de destino configurada. Configura la ruta de carpeta antes de organizar evidencias.');
        }

        $evidenceIds = $validated['evidence_ids'];

        $result = $this->evidenceOrganizerService->organizeEvidences($cut, $evidenceIds);

        return back()->with('organization_result', [
            'success_count' => $result->successCount,
            'failure_count' => $result->failureCount,
            'succeeded' => $result->succeeded,
            'failed' => $result->failed,
        ]);
    }

    /**
     * Open the cut's folder in the system file explorer.
     * Returns a .vbs file that opens the folder when executed.
     */
    public function openFolder(Cut $cut)
    {
        if (empty($cut->folder_path) || !is_dir($cut->folder_path)) {
            return back()->with('error', 'La carpeta del corte no existe o no está configurada.');
        }

        $path = str_replace('/', '\\', $cut->folder_path);
        // VBScript is more reliable than .bat for opening explorer
        $vbsContent = "CreateObject(\"WScript.Shell\").Run \"explorer.exe \"\"$path\"\"\", 1, False\r\nWScript.Quit";

        return response($vbsContent)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="abrir_carpeta.vbs"');
    }

    private function syncCutServiceRequests(Cut $cut): void
    {
        [$start, $end] = $cut->getDateRangeForQuery();

        $query = ServiceRequest::query()
            ->eligibleForCutAssignment()
            ->when($cut->contract_id, function ($q) use ($cut) {
                $q->whereHas('subService.service.family', function ($fq) use ($cut) {
                    $fq->where('contract_id', $cut->contract_id);
                });
            });

        // Open cuts capture everything from start_date onward (no end_date limit)
        if ($cut->isOpen()) {
            $query->where(function ($q) use ($start) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) >= ?', [$start]);
            });
        } else {
            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
            });
        }

        $requestIds = $query->pluck('id')->all();

        if ($cut->contract_id && !empty($requestIds)) {
            $siblingCutIds = Cut::query()
                ->where('contract_id', $cut->contract_id)
                ->where('id', '!=', $cut->id)
                ->pluck('id');

            if ($siblingCutIds->isNotEmpty()) {
                DB::table('cut_service_request')
                    ->whereIn('cut_id', $siblingCutIds)
                    ->whereIn('service_request_id', $requestIds)
                    ->delete();
            }
        }

        // Determine which requests are newly being added to this cut
        $currentRequestIds = $cut->serviceRequests()->pluck('service_requests.id')->all();
        $newRequestIds = array_diff($requestIds, $currentRequestIds);

        $cut->serviceRequests()->sync($requestIds);

        // Relocate evidence files for newly added requests to this cut's folder
        if (!empty($newRequestIds) && !empty($cut->folder_path)) {
            $this->evidenceOrganizerService->relocateEvidences($cut, $newRequestIds);
        }
    }

    private function formatFamilyLabel($family): string
    {
        $familyName = $family?->name ?? 'Sin Familia';
        $contractNumber = $family?->contract?->number;

        return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
    }

    private function buildFamilyInfoText($family): string
    {
        $familyName = $this->formatFamilyLabel($family);
        $familyDescription = trim((string) ($family?->description ?? ''));

        if ($familyDescription === '') {
            $familyDescription = 'Sin descripción registrada para esta familia.';
        }

        return "Nombre: {$familyName}\nDescripción: {$familyDescription}\n";
    }

    private function generateZipWithEvidences(array $reportData, string $baseFileName)
    {
        if (!class_exists('ZipArchive')) {
            return back()->with('error', 'La extensión ZIP no está habilitada. Intenta con PDF o contacta al administrador.');
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = storage_path("app/temp/{$baseFileName}.zip");
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo generar el archivo ZIP.');
        }

        $cut = $reportData['cut'];
        $serviceRequests = collect($reportData['serviceRequests'] ?? []);
        $requestsByFamily = $serviceRequests->groupBy(function ($request) {
            return (int) ($request->subService?->service?->family?->id ?? 0);
        });

        $familiesProcessed = 0;
        $pdfCount = 0;
        $evidencesAdded = 0;

        foreach ($requestsByFamily as $familyRequests) {
            if ($familyRequests->isEmpty()) {
                continue;
            }

            $family = $familyRequests->first()?->subService?->service?->family;
            $familyLabel = $this->formatFamilyLabel($family);
            $familyFolderName = $this->buildFamilyFolderName($family);
            $familyRoot = $familyFolderName;

            $familyGroupedData = collect([$familyLabel => $familyRequests]);
            $familyEvidences = $familyRequests
                ->flatMap(fn($sr) => $sr->evidences ?? collect())
                ->sortByDesc('created_at')
                ->values();

            $familyPdfData = [
                'cut' => $cut,
                'serviceRequests' => $familyRequests,
                'groupedData' => $familyGroupedData,
                'generatedAt' => now(),
                'generatedBy' => (string) ($reportData['generatedBy'] ?? 'Sistema'),
                'generatedByEmail' => (string) ($reportData['generatedByEmail'] ?? ''),
                'generatedByDependency' => (string) ($reportData['generatedByDependency'] ?? ''),
                'selectedFamilyLabels' => collect([$familyLabel]),
                'evidences' => $familyEvidences,
            ];

            $pdfContent = Pdf::loadView('reports.cuts.pdf', $familyPdfData)
                ->setPaper('a4', 'portrait')
                ->output();
            $zip->addFromString("{$familyRoot}/reporte.pdf", $pdfContent);
            $zip->addFromString("{$familyRoot}/descripcion.txt", $this->buildFamilyInfoText($family));
            $pdfCount++;
            $familiesProcessed++;

            foreach ($familyEvidences as $evidence) {
                $storagePath = $this->resolveEvidenceStoragePath((string) ($evidence->file_path ?? ''));
                if (!$storagePath) {
                    continue;
                }

                $ticket = $evidence->serviceRequest?->ticket_number ?: ('SR-' . $evidence->service_request_id);
                $ticketFolder = preg_replace('/[^A-Za-z0-9_-]/', '-', $ticket);
                $fileName = $this->sanitizeFileName($evidence->file_original_name ?: basename($storagePath));

                try {
                    $content = Storage::disk('public')->get($storagePath);
                    $zip->addFromString("{$familyRoot}/evidencias/{$ticketFolder}/{$fileName}", $content);
                    $evidencesAdded++;
                } catch (\Throwable $e) {
                    Log::warning('No se pudo incluir evidencia en ZIP', [
                        'evidence_id' => $evidence->id ?? null,
                        'path' => $storagePath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $baseFileName . '.zip')->deleteFileAfterSend();
    }

    private function resolveEvidenceStoragePath(string $filePath): ?string
    {
        if ($filePath === '' || preg_match('#^https?://#i', $filePath)) {
            return null;
        }

        $normalized = ltrim($filePath, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        $candidates = array_filter(array_unique([
            $filePath,
            $normalized,
            $normalized ? ('evidences/' . basename($normalized)) : null,
            basename($filePath) ? ('evidences/' . basename($filePath)) : null,
        ]));

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function sanitizeFileName(string $fileName): string
    {
        $pathInfo = pathinfo($fileName);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $base = $pathInfo['filename'] ?? 'archivo';

        $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $base);
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        $sanitized = trim((string) $sanitized, '._');
        if ($sanitized === '') {
            $sanitized = 'archivo';
        }

        $maxLength = 120 - strlen($extension);
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }

        return $sanitized . $extension;
    }

    private function buildFamilyFolderName($family): string
    {
        $sortOrder = (int) ($family?->sort_order ?? 0);
        $familyName = $family?->name ?? 'Sin Familia';

        // Sanitize for filesystem: remove invalid chars, limit length
        $slug = preg_replace('/[\\\\\/:"*?<>|]+/', '-', $familyName);
        $slug = preg_replace('/\s+/', ' ', trim($slug));
        $slug = mb_substr($slug, 0, 90);

        return $sortOrder > 0 ? "{$sortOrder} - {$slug}" : $slug;
    }

    private function generateFamilyPdfPackage(
        Cut $cut,
        $serviceRequests,
        $families,
        array $selectedFamilyIds,
        string $baseFileName,
        string $generatedBy = 'Sistema',
        string $generatedByEmail = '',
        string $generatedByDependency = ''
    ) {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Use filesystem-based approach: write to temp folder then compress with tar
        $buildDir = storage_path("app/temp/{$baseFileName}");
        if (is_dir($buildDir)) {
            $cleanIt = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($buildDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($cleanIt as $item) {
                $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
            }
            rmdir($buildDir);
        }
        mkdir($buildDir, 0755, true);

        $requestsByFamilyId = $serviceRequests->groupBy(function ($request) {
            return (int) ($request->subService?->service?->family?->id ?? 0);
        });

        $singleFamilyPackage = count($selectedFamilyIds) === 1;
        $evidencesAdded = 0;
        $pdfCount = 0;

        foreach ($selectedFamilyIds as $familyId) {
            $family = $families->firstWhere('id', $familyId);
            if (!$family) {
                continue;
            }

            $familyRequests = $requestsByFamilyId->get((int) $familyId, collect());
            $familyFolderName = $this->buildFamilyFolderName($family);
            $familyRoot = $singleFamilyPackage ? $buildDir : $buildDir . DIRECTORY_SEPARATOR . $familyFolderName;

            if (!is_dir($familyRoot)) {
                mkdir($familyRoot, 0755, true);
            }

            file_put_contents($familyRoot . DIRECTORY_SEPARATOR . 'descripcion.txt', $this->buildFamilyInfoText($family));

            if ($familyRequests->isEmpty()) {
                continue;
            }

            $familyLabel = $this->formatFamilyLabel($family);
            $groupedData = collect([$familyLabel => $familyRequests]);
            $familyEvidences = $familyRequests
                ->flatMap(fn($sr) => $sr->evidences ?? collect())
                ->sortByDesc('created_at')
                ->values();

            $pdfData = [
                'cut' => $cut,
                'serviceRequests' => $familyRequests,
                'groupedData' => $groupedData,
                'generatedAt' => now(),
                'generatedBy' => $generatedBy,
                'generatedByEmail' => $generatedByEmail,
                'generatedByDependency' => $generatedByDependency,
                'selectedFamilyLabels' => collect([$familyLabel]),
                'evidences' => $familyEvidences,
            ];

            $pdfContent = Pdf::loadView('reports.cuts.pdf', $pdfData)
                ->setPaper('a4', 'portrait')
                ->output();

            file_put_contents($familyRoot . DIRECTORY_SEPARATOR . 'reporte.pdf', $pdfContent);
            $pdfCount++;

            foreach ($familyRequests as $serviceRequest) {
                foreach (($serviceRequest->evidences ?? collect()) as $evidence) {
                    $storagePath = $this->resolveEvidenceStoragePath((string) ($evidence->file_path ?? ''));
                    if (!$storagePath) {
                        continue;
                    }

                    $ticket = $serviceRequest->ticket_number ?: ('SR-' . $serviceRequest->id);
                    $ticketFolder = preg_replace('/[^A-Za-z0-9_-]/', '-', $ticket);
                    $fileName = $this->sanitizeFileName($evidence->file_original_name ?: basename($storagePath));

                    $evidenceDir = $familyRoot . DIRECTORY_SEPARATOR . 'evidencias' . DIRECTORY_SEPARATOR . $ticketFolder;
                    if (!is_dir($evidenceDir)) {
                        mkdir($evidenceDir, 0755, true);
                    }

                    $destPath = $evidenceDir . DIRECTORY_SEPARATOR . $fileName;
                    if (file_exists($destPath)) {
                        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                        $base = pathinfo($fileName, PATHINFO_FILENAME);
                        $fileName = $base . '_' . ($evidence->id ?? rand(1, 999)) . '.' . $ext;
                        $destPath = $evidenceDir . DIRECTORY_SEPARATOR . $fileName;
                    }

                    try {
                        $content = Storage::disk('public')->get($storagePath);
                        file_put_contents($destPath, $content);
                        $evidencesAdded++;
                    } catch (\Throwable $e) {
                        Log::warning('No se pudo incluir evidencia en paquete de PDFs por familia', [
                            'evidence_id' => $evidence->id ?? null,
                            'path' => $storagePath,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Compress using PHP ZipArchive
        $zipPath = storage_path("app/temp/{$baseFileName}.zip");
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            // Cleanup build directory
            $this->removeBuildDirectory($buildDir);
            return back()->with('error', 'No se pudo crear el archivo ZIP del reporte.');
        }

        // Recursively add files from buildDir to zip
        $buildDirRealPath = realpath($buildDir);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($buildDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($buildDirRealPath) + 1);
                $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
            }
        }

        $zip->close();

        // Cleanup build directory
        $this->removeBuildDirectory($buildDir);

        if (!file_exists($zipPath)) {
            return back()->with('error', 'No se pudo generar el archivo ZIP del reporte.');
        }

        return response()->download($zipPath, $baseFileName . '.zip')->deleteFileAfterSend();
    }

    private function removeBuildDirectory(string $buildDir): void
    {
        if (!is_dir($buildDir)) {
            return;
        }

        $cleanIt = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($buildDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($cleanIt as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($buildDir);
    }
}
