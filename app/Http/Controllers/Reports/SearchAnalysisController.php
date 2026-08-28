<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceFamily;
use App\Models\Service;
use App\Models\SubService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SearchAnalysisController extends Controller
{
    /**
     * Display the search form with text input and multi-select for service types.
     */
    public function index(): View
    {
        $currentCompanyId = (int) session('current_company_id');

        $families = ServiceFamily::query()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->active()
            ->ordered()
            ->get();

        $services = Service::query()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->active()
            ->ordered()
            ->get();

        $subServices = SubService::query()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('service.family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->active()
            ->ordered()
            ->get();

        return view('reports.search-analysis.index', compact('families', 'services', 'subServices'));
    }

    /**
     * Perform search with case-insensitive partial match across multiple fields.
     * OR logic between terms; AND with service type filters.
     * Paginated 50/page, ordered by created_at desc.
     */
    public function search(Request $request): View|RedirectResponse
    {
        // Check at least one input is provided (terms or service filters)
        $terms = $request->input('terms');
        $hasTerms = !empty(trim($terms ?? ''));
        $hasFilters = !empty($request->input('families')) || !empty($request->input('services')) || !empty($request->input('sub_services'));

        if (!$hasTerms && !$hasFilters) {
            return back()->withErrors(['terms' => 'Ingrese al menos un término de búsqueda o seleccione un tipo de servicio.'])->withInput();
        }

        $request->validate(
            $this->getValidationRules(),
            $this->getValidationMessages()
        );

        $currentCompanyId = (int) session('current_company_id');

        // Parse search terms
        $searchTerms = $this->parseSearchTerms($request->input('terms', ''));

        // Get selected service type filters
        $selectedFamilies = $request->input('families', []);
        $selectedServices = $request->input('services', []);
        $selectedSubServices = $request->input('sub_services', []);

        // Build query
        $query = ServiceRequest::query()
            ->with(['subService.service.family', 'requester'])
            ->reportable();

        // Apply search terms (OR logic between terms)
        if (!empty($searchTerms)) {
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere(function ($subQuery) use ($term) {
                        $subQuery->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('description', 'LIKE', "%{$term}%")
                            ->orWhere('resolution_notes', 'LIKE', "%{$term}%")
                            ->orWhereHas('requester', function ($rq) use ($term) {
                                $rq->where('name', 'LIKE', "%{$term}%")
                                    ->orWhere('email', 'LIKE', "%{$term}%")
                                    ->orWhere('department', 'LIKE', "%{$term}%");
                            });
                    });
                }
            });
        }

        // Apply service type filters (AND with search terms, OR between service types)
        if (!empty($selectedFamilies) || !empty($selectedServices) || !empty($selectedSubServices)) {
            $query->where(function ($q) use ($selectedFamilies, $selectedServices, $selectedSubServices) {
                if (!empty($selectedSubServices)) {
                    $q->orWhereIn('sub_service_id', $selectedSubServices);
                }
                if (!empty($selectedServices)) {
                    $q->orWhereHas('subService', function ($sq) use ($selectedServices) {
                        $sq->whereIn('service_id', $selectedServices);
                    });
                }
                if (!empty($selectedFamilies)) {
                    $q->orWhereHas('subService.service', function ($sq) use ($selectedFamilies) {
                        $sq->whereIn('service_family_id', $selectedFamilies);
                    });
                }
            });
        }

        // Order by created_at desc
        $query->orderByDesc('created_at');

        // Get total count for summary before pagination
        $totalQuery = clone $query;
        $totalMatches = $totalQuery->count();

        // Build summary data
        $summary = $this->buildSummary($query);

        // Paginate results (50 per page)
        $results = $query->paginate(50)->appends($request->all());

        // Load service type options for the form
        $families = ServiceFamily::query()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->active()
            ->ordered()
            ->get();

        $services = Service::query()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->active()
            ->ordered()
            ->get();

        $subServices = SubService::query()
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('service.family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->active()
            ->ordered()
            ->get();

        $selectedServiceTypes = array_merge(
            array_map(fn($id) => "family_{$id}", $selectedFamilies),
            array_map(fn($id) => "service_{$id}", $selectedServices),
            array_map(fn($id) => "sub_service_{$id}", $selectedSubServices)
        );

        return view('reports.search-analysis.results', compact(
            'results',
            'summary',
            'searchTerms',
            'selectedFamilies',
            'selectedServices',
            'selectedSubServices',
            'selectedServiceTypes',
            'families',
            'services',
            'subServices'
        ));
    }

    /**
     * Export search results in PDF or CSV format.
     */
    public function export(Request $request, string $format): Response|RedirectResponse
    {
        // Check at least one input is provided (terms or service filters)
        $terms = $request->input('terms');
        $hasTerms = !empty(trim($terms ?? ''));
        $hasFilters = !empty($request->input('families')) || !empty($request->input('services')) || !empty($request->input('sub_services'));

        if (!$hasTerms && !$hasFilters) {
            return back()->withErrors(['terms' => 'Ingrese al menos un término de búsqueda o seleccione un tipo de servicio.'])->withInput();
        }

        $request->validate(
            $this->getValidationRules(),
            $this->getValidationMessages()
        );

        $currentCompanyId = (int) session('current_company_id');

        // Parse search terms
        $searchTerms = $this->parseSearchTerms($request->input('terms', ''));

        // Get selected service type filters
        $selectedFamilies = $request->input('families', []);
        $selectedServices = $request->input('services', []);
        $selectedSubServices = $request->input('sub_services', []);

        // Build query (same logic as search)
        $query = ServiceRequest::query()
            ->with(['subService.service.family', 'requester'])
            ->reportable();

        if (!empty($searchTerms)) {
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere(function ($subQuery) use ($term) {
                        $subQuery->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('description', 'LIKE', "%{$term}%")
                            ->orWhere('resolution_notes', 'LIKE', "%{$term}%")
                            ->orWhereHas('requester', function ($rq) use ($term) {
                                $rq->where('name', 'LIKE', "%{$term}%")
                                    ->orWhere('email', 'LIKE', "%{$term}%")
                                    ->orWhere('department', 'LIKE', "%{$term}%");
                            });
                    });
                }
            });
        }

        if (!empty($selectedFamilies) || !empty($selectedServices) || !empty($selectedSubServices)) {
            $query->where(function ($q) use ($selectedFamilies, $selectedServices, $selectedSubServices) {
                if (!empty($selectedSubServices)) {
                    $q->orWhereIn('sub_service_id', $selectedSubServices);
                }
                if (!empty($selectedServices)) {
                    $q->orWhereHas('subService', function ($sq) use ($selectedServices) {
                        $sq->whereIn('service_id', $selectedServices);
                    });
                }
                if (!empty($selectedFamilies)) {
                    $q->orWhereHas('subService.service', function ($sq) use ($selectedFamilies) {
                        $sq->whereIn('service_family_id', $selectedFamilies);
                    });
                }
            });
        }

        $query->orderByDesc('created_at');
        $results = $query->get();
        $summary = $this->buildSummaryFromCollection($results);

        $timestamp = now()->format('Y-m-d_His');

        try {
            if ($format === 'pdf') {
                return $this->exportPdf($results, $summary, $searchTerms, $timestamp);
            }

            if ($format === 'csv') {
                return $this->exportCsv($results, $summary, $searchTerms, $timestamp);
            }

            return back()->with('error', 'Formato de exportación no válido. Use pdf o csv.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar el archivo: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    /**
     * Parse comma-separated search terms, trimming whitespace.
     */
    private function parseSearchTerms(?string $input): array
    {
        if ($input === null || empty(trim($input))) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $input)),
            fn($term) => $term !== ''
        ));
    }

    /**
     * Build summary statistics from the query (before pagination).
     */
    private function buildSummary($query): array
    {
        $clonedQuery = clone $query;
        $allResults = $clonedQuery->get();

        return $this->buildSummaryFromCollection($allResults);
    }

    /**
     * Build summary statistics from a collection of results.
     */
    private function buildSummaryFromCollection($results): array
    {
        $totalMatches = $results->count();

        $byStatus = $results->groupBy('status')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();

        $byFamily = $results->groupBy(function ($item) {
            return $item->subService?->service?->family?->name ?? 'Sin familia';
        })
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();

        $byCriticality = $results->groupBy('criticality_level')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();

        return [
            'total_matches' => $totalMatches,
            'by_status' => $byStatus,
            'by_family' => $byFamily,
            'by_criticality' => $byCriticality,
        ];
    }

    /**
     * Get validation rules for search/export requests.
     */
    private function getValidationRules(): array
    {
        return [
            'terms' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (!empty(trim($value ?? ''))) {
                    $terms = array_filter(array_map('trim', explode(',', $value)), fn($t) => $t !== '');

                    if (count($terms) > 10) {
                        $fail('Máximo 10 términos de búsqueda.');
                    }

                    foreach ($terms as $term) {
                        if (mb_strlen($term) > 100) {
                            $fail('Cada término debe tener máximo 100 caracteres.');
                            break;
                        }
                    }
                }
            }],
            'families' => ['nullable', 'array'],
            'families.*' => ['integer', 'exists:service_families,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
            'sub_services' => ['nullable', 'array'],
            'sub_services.*' => ['integer', 'exists:sub_services,id'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    private function getValidationMessages(): array
    {
        return [
            'families.*.exists' => 'La familia de servicio seleccionada no es válida.',
            'services.*.exists' => 'El servicio seleccionado no es válido.',
            'sub_services.*.exists' => 'El sub-servicio seleccionado no es válido.',
        ];
    }

    /**
     * Export results to PDF.
     */
    private function exportPdf($results, array $summary, array $searchTerms, string $timestamp): Response
    {
        $data = [
            'results' => $results,
            'summary' => $summary,
            'searchTerms' => $searchTerms,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];

        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('reports.exports.search-analysis-pdf', $data);
            return $pdf->download("busqueda-analisis-{$timestamp}.pdf");
        }

        $html = view('reports.exports.search-analysis-pdf', $data)->render();
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"busqueda-analisis-{$timestamp}.pdf\"",
        ]);
    }

    /**
     * Export results to CSV.
     */
    private function exportCsv($results, array $summary, array $searchTerms, string $timestamp): Response
    {
        $csv = "BÚSQUEDA Y ANÁLISIS\n";
        $csv .= "Generado: " . now()->format('d/m/Y H:i') . "\n";
        $csv .= "Términos: " . implode(', ', $searchTerms) . "\n";
        $csv .= "Total resultados: {$summary['total_matches']}\n\n";

        // Summary section
        $csv .= "=== RESUMEN POR ESTADO ===\n";
        $csv .= "Estado,Cantidad\n";
        foreach ($summary['by_status'] as $status => $count) {
            $csv .= "\"{$status}\",{$count}\n";
        }

        $csv .= "\n=== RESUMEN POR FAMILIA ===\n";
        $csv .= "Familia,Cantidad\n";
        foreach ($summary['by_family'] as $family => $count) {
            $csv .= "\"{$family}\",{$count}\n";
        }

        $csv .= "\n=== RESUMEN POR CRITICIDAD ===\n";
        $csv .= "Nivel,Cantidad\n";
        foreach ($summary['by_criticality'] as $level => $count) {
            $csv .= "\"{$level}\",{$count}\n";
        }

        // Results section
        $csv .= "\n=== RESULTADOS ===\n";
        $csv .= "Ticket,Título,Estado,Servicio,Familia,Criticidad,Fecha Creación,Fecha Resolución,Solicitante\n";
        foreach ($results as $item) {
            $serviceName = $item->subService?->service?->name ?? 'N/A';
            $familyName = $item->subService?->service?->family?->name ?? 'N/A';
            $requesterName = $item->requester?->name ?? 'N/A';
            $resolvedAt = $item->resolved_at ? Carbon::parse($item->resolved_at)->format('d/m/Y') : '';
            $createdAt = $item->created_at ? $item->created_at->format('d/m/Y') : '';

            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $item->ticket_number ?? '',
                str_replace('"', '""', $item->title ?? ''),
                $item->status ?? '',
                $serviceName,
                $familyName,
                $item->criticality_level ?? '',
                $createdAt,
                $resolvedAt,
                str_replace('"', '""', $requesterName)
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"busqueda-analisis-{$timestamp}.csv\"",
        ]);
    }
}
