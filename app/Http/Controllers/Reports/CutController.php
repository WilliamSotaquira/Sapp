<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cut;
use App\Models\ServiceFamily;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use ZipArchive;

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
            ->active()
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

        return view('reports.cuts.show', compact(
            'cut',
            'serviceRequests',
            'families',
            'selectedFamilyIds',
            'selectedFamilyLabels',
            'familyRequestCounts'
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
            ->active()
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

    private function formatFamilyLabel($family): string
    {
        $familyName = $family?->name ?? 'Sin Familia';
        $contractNumber = $family?->contract?->number;

        return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
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
            $familyFolderName = $this->buildFamilyFolderName((int) ($family?->id ?? 0), $familyLabel);
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

    private function buildFamilyFolderName(int $familyId, string $familyLabel): string
    {
        $slug = Str::slug($familyLabel, '-');
        if ($slug === '') {
            $slug = 'familia';
        }

        // Keep names concise and stable in ZIP explorers.
        $slug = Str::limit($slug, 90, '');

        return str_pad((string) max(0, $familyId), 2, '0', STR_PAD_LEFT) . '-' . $slug;
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
        if (!class_exists('ZipArchive')) {
            return back()->with('error', 'La extensión ZIP no está habilitada. No es posible generar la carpeta de reportes.');
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = storage_path("app/temp/{$baseFileName}.zip");
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo generar el archivo ZIP del reporte.');
        }

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

            $familyFolderName = $this->buildFamilyFolderName((int) $family->id, $familyLabel);
            $familyRoot = $singleFamilyPackage ? '' : $familyFolderName;
            $reportPath = $familyRoot === '' ? 'reporte.pdf' : "{$familyRoot}/reporte.pdf";
            $zip->addFromString($reportPath, $pdfContent);
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

                    try {
                        $content = Storage::disk('public')->get($storagePath);
                        $evidencePath = $familyRoot === ''
                            ? "evidencias/{$ticketFolder}/{$fileName}"
                            : "{$familyRoot}/evidencias/{$ticketFolder}/{$fileName}";
                        $zip->addFromString($evidencePath, $content);
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

        $zip->close();

        return response()->download($zipPath, $baseFileName . '.zip')->deleteFileAfterSend();
    }
}
