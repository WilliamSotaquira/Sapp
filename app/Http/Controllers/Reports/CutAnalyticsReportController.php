<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Cut;
use App\Models\ServiceFamily;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CutAnalyticsReportController extends Controller
{
    public function show(Cut $cut, Request $request)
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? Company::with('activeContract')->find($currentCompanyId)
            : null;

        $this->authorizeCut($cut, $currentCompanyId, $currentCompany?->active_contract_id);

        $families = $this->getFamiliesForCut($cut, $currentCompanyId);
        $selectedFamilyIds = $this->resolveSelectedFamilyIds($request, $families);
        $analytics = $this->buildAnalytics($cut, $selectedFamilyIds);

        return view('reports.cuts.analytics', [
            'cut' => $cut,
            'families' => $families,
            'selectedFamilyIds' => $selectedFamilyIds,
            'analytics' => $analytics,
        ]);
    }

    public function exportCsv(Cut $cut, Request $request)
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? Company::with('activeContract')->find($currentCompanyId)
            : null;

        $this->authorizeCut($cut, $currentCompanyId, $currentCompany?->active_contract_id);

        $families = $this->getFamiliesForCut($cut, $currentCompanyId);
        $selectedFamilyIds = $this->resolveSelectedFamilyIds($request, $families);
        $analytics = $this->buildAnalytics($cut, $selectedFamilyIds);

        $csv = $this->buildCsv($cut, $analytics);
        $fileName = 'informe-analitico-corte-' . $cut->id . '-' . now()->format('Y-m-d_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportPdf(Cut $cut, Request $request)
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? Company::with('activeContract')->find($currentCompanyId)
            : null;

        $this->authorizeCut($cut, $currentCompanyId, $currentCompany?->active_contract_id);

        $families = $this->getFamiliesForCut($cut, $currentCompanyId);
        $selectedFamilyIds = $this->resolveSelectedFamilyIds($request, $families);
        $analytics = $this->buildAnalytics($cut, $selectedFamilyIds);

        $pdf = Pdf::loadView('reports.cuts.analytics-pdf', [
            'cut' => $cut,
            'analytics' => $analytics,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'informe-analitico-corte-' . $cut->id . '-' . now()->format('Y-m-d_His') . '.pdf'
        );
    }

    private function authorizeCut(Cut $cut, int $currentCompanyId, ?int $activeContractId): void
    {
        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }

        if ($activeContractId && (int) $cut->contract_id !== $activeContractId) {
            abort(403);
        }
    }

    private function getFamiliesForCut(Cut $cut, int $currentCompanyId): Collection
    {
        return ServiceFamily::query()
            ->active()
            ->when($cut->contract_id, fn ($query) => $query->where('contract_id', $cut->contract_id))
            ->when($currentCompanyId, function ($query) use ($currentCompanyId) {
                $query->whereHas('contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            })
            ->with('contract:id,number')
            ->ordered()
            ->get();
    }

    private function resolveSelectedFamilyIds(Request $request, Collection $families): array
    {
        $validFamilyIds = $families->pluck('id')->all();

        return collect($request->input('families', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $validFamilyIds, true))
            ->unique()
            ->values()
            ->all();
    }

    private function buildAnalytics(Cut $cut, array $selectedFamilyIds): array
    {
        $requests = $cut->serviceRequests()
            ->with(['requester:id,name,department', 'subService.service.family.contract'])
            ->when(!empty($selectedFamilyIds), function ($query) use ($selectedFamilyIds) {
                $query->whereHas('subService.service.family', function ($q) use ($selectedFamilyIds) {
                    $q->whereIn('service_families.id', $selectedFamilyIds);
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'total' => $requests->count(),
            'completed' => $requests->whereIn('status', ['RESUELTA', 'CERRADA'])->count(),
            'active' => $requests->whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'])->count(),
            'cancelled' => $requests->whereIn('status', ['CANCELADA', 'RECHAZADA'])->count(),
            'distinct_areas' => $requests->map(fn ($request) => $this->resolveDepartment($request))->unique()->count(),
            'distinct_channels' => $requests->map(fn ($request) => $this->entryChannelLabel($request->entry_channel))->unique()->count(),
            'distinct_routes' => $requests->map(fn ($request) => $this->resolveMainRoute($request))->unique()->count(),
        ];

        $completionRate = $summary['total'] > 0
            ? round(($summary['completed'] / $summary['total']) * 100, 1)
            : 0.0;

        $distributions = [
            'status' => $this->distribution($requests, fn ($request) => $this->statusLabel($request->status)),
            'channels' => $this->distribution($requests, fn ($request) => $this->entryChannelLabel($request->entry_channel)),
            'areas' => $this->distribution($requests, fn ($request) => $this->resolveDepartment($request)),
            'families' => $this->distribution($requests, fn ($request) => $this->resolveFamily($request)),
            'services' => $this->distribution($requests, fn ($request) => $this->resolveService($request)),
            'subservices' => $this->distribution($requests, fn ($request) => $this->resolveSubservice($request)),
            'routes' => $this->distribution($requests, fn ($request) => $this->resolveMainRoute($request)),
        ];

        $detailRows = $requests->map(function ($request) {
            return [
                'ticket' => (string) $request->ticket_number,
                'title' => (string) $request->title,
                'status' => $this->statusLabel($request->status),
                'channel' => $this->entryChannelLabel($request->entry_channel),
                'area' => $this->resolveDepartment($request),
                'family' => $this->resolveFamily($request),
                'service' => $this->resolveService($request),
                'subservice' => $this->resolveSubservice($request),
                'route' => $this->resolveMainRoute($request),
                'created_at' => $request->created_at?->format('Y-m-d H:i'),
                'resolved_at' => $request->resolved_at?->format('Y-m-d H:i'),
            ];
        });

        return [
            'summary' => $summary + ['completion_rate' => $completionRate],
            'selected_family_labels' => $requests
                ->map(fn ($request) => $this->resolveFamily($request))
                ->unique()
                ->values(),
            'distributions' => $distributions,
            'findings' => $this->buildFindings($summary, $completionRate, $distributions),
            'recommendations' => $this->buildRecommendations($summary, $distributions),
            'assumptions' => [
                'El informe usa los datos actualmente registrados en tickets del sistema asociados al corte.',
                'La variable de area se calcula con la dependencia del solicitante registrada en el sistema.',
                'La variable de canal corresponde al canal de entrada de la solicitud; no a un canal editorial capturado aparte.',
                'Tema y clasificacion operativa se leen desde familia, servicio y subservicio del ticket.',
                'Ruta principal web se muestra cuando la solicitud la tiene diligenciada; en caso contrario queda como Sin ruta registrada.',
            ],
            'detail_rows' => $detailRows,
        ];
    }

    private function distribution(Collection $requests, callable $resolver): Collection
    {
        $total = $requests->count();

        if ($total === 0) {
            return collect();
        }

        return $requests
            ->groupBy(function ($request) use ($resolver) {
                return trim((string) $resolver($request)) ?: 'Sin dato';
            })
            ->map(function ($group, $label) use ($total) {
                return [
                    'label' => (string) $label,
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $total) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    private function buildFindings(array $summary, float $completionRate, array $distributions): array
    {
        $findings = [];

        $topArea = $distributions['areas']->first();
        if ($topArea) {
            $findings[] = "El area con mayor volumen en el corte es {$topArea['label']} con {$topArea['count']} registro(s), equivalente al {$topArea['percentage']}%.";
        }

        $topChannel = $distributions['channels']->first();
        if ($topChannel) {
            $findings[] = "El canal de entrada predominante es {$topChannel['label']} con {$topChannel['count']} solicitud(es).";
        }

        $topSubservice = $distributions['subservices']->first();
        if ($topSubservice) {
            $findings[] = "La categoria operativa mas frecuente en este corte es {$topSubservice['label']} con {$topSubservice['count']} caso(s).";
        }

        $findings[] = "La tasa de cierre o resolucion del corte es {$completionRate}% sobre un total de {$summary['total']} solicitud(es).";

        $routes = $distributions['routes'];
        if ($routes->isNotEmpty() && $routes->first()['label'] === 'Sin ruta registrada') {
            $findings[] = 'La mayor parte del corte no tiene ruta principal web diligenciada, lo que limita el analisis editorial por canal publicado.';
        }

        return $findings;
    }

    private function buildRecommendations(array $summary, array $distributions): array
    {
        $recommendations = [];

        if ($summary['active'] > $summary['completed']) {
            $recommendations[] = 'Priorizar seguimiento de las solicitudes activas del corte para mejorar el porcentaje de cierre antes del siguiente informe.';
        }

        $routes = $distributions['routes'];
        if ($routes->isNotEmpty() && $routes->first()['label'] === 'Sin ruta registrada') {
            $recommendations[] = 'Estandarizar el diligenciamiento de la ruta principal web para diferenciar mejor web, micrositios, intranet u otros destinos.';
        }

        $areas = $distributions['areas'];
        if ($areas->count() > 0 && $areas->first()['percentage'] >= 40) {
            $recommendations[] = 'Revisar balance de demanda entre dependencias, ya que una sola area concentra una parte alta del flujo del corte.';
        }

        $channels = $distributions['channels'];
        if ($channels->count() === 1) {
            $recommendations[] = 'Mantener control de calidad sobre el canal unico registrado y evaluar si hace falta capturar otros canales operativos o editoriales.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Mantener la captura consistente de area, canal, servicio y ruta para conservar comparabilidad entre cortes.';
        }

        return $recommendations;
    }

    private function buildCsv(Cut $cut, array $analytics): string
    {
        $lines = [];
        $lines[] = "\xEF\xBB\xBFInforme analitico por corte";
        $lines[] = $this->csvLine(['Corte', $cut->name]);
        $lines[] = $this->csvLine(['Rango', $cut->start_date->format('Y-m-d') . ' a ' . $cut->end_date->format('Y-m-d')]);
        $lines[] = $this->csvLine(['Generado', now()->format('Y-m-d H:i:s')]);
        $lines[] = '';

        $lines[] = 'Resumen';
        $lines[] = $this->csvLine(['Indicador', 'Valor']);
        foreach ($analytics['summary'] as $label => $value) {
            $lines[] = $this->csvLine([$label, (string) $value]);
        }
        $lines[] = '';

        foreach (['status', 'channels', 'areas', 'families', 'services', 'subservices', 'routes'] as $key) {
            $lines[] = strtoupper($key);
            $lines[] = $this->csvLine(['Label', 'Cantidad', 'Porcentaje']);
            foreach ($analytics['distributions'][$key] as $row) {
                $lines[] = $this->csvLine([$row['label'], $row['count'], $row['percentage']]);
            }
            $lines[] = '';
        }

        $lines[] = 'Detalle';
        $lines[] = $this->csvLine([
            'Ticket',
            'Titulo',
            'Estado',
            'Canal',
            'Area',
            'Familia',
            'Servicio',
            'Subservicio',
            'Ruta principal',
            'Fecha creacion',
            'Fecha resolucion',
        ]);

        foreach ($analytics['detail_rows'] as $row) {
            $lines[] = $this->csvLine([
                $row['ticket'],
                $row['title'],
                $row['status'],
                $row['channel'],
                $row['area'],
                $row['family'],
                $row['service'],
                $row['subservice'],
                $row['route'],
                $row['created_at'],
                $row['resolved_at'],
            ]);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    private function csvLine(array $values): string
    {
        return collect($values)->map(function ($value) {
            $escaped = str_replace('"', '""', (string) $value);
            return '"' . $escaped . '"';
        })->implode(',');
    }

    private function entryChannelLabel(?string $channel): string
    {
        return ServiceRequest::getEntryChannelOptions()[$channel]['label'] ?? 'Sin canal registrado';
    }

    private function statusLabel(?string $status): string
    {
        return ServiceRequest::getStatusOptions()[$status] ?? ($status ?: 'Sin estado');
    }

    private function resolveDepartment($request): string
    {
        return trim((string) optional($request->requester)->department) ?: 'Sin area registrada';
    }

    private function resolveFamily($request): string
    {
        $family = $request->subService?->service?->family;
        $familyName = trim((string) ($family?->name ?? 'Sin familia'));
        $contractNumber = trim((string) ($family?->contract?->number ?? ''));

        return $contractNumber !== '' ? $contractNumber . ' - ' . $familyName : $familyName;
    }

    private function resolveService($request): string
    {
        return trim((string) ($request->subService?->service?->name ?? 'Sin servicio'));
    }

    private function resolveSubservice($request): string
    {
        return trim((string) ($request->subService?->name ?? 'Sin subservicio'));
    }

    private function resolveMainRoute($request): string
    {
        $mainRoute = trim((string) ($request->main_web_route ?? ''));
        if ($mainRoute !== '') {
            return $mainRoute;
        }

        $routes = $request->web_routes;
        if (is_array($routes) && !empty($routes)) {
            $first = $routes[0];
            if (is_array($first)) {
                $candidate = trim((string) ($first['url'] ?? $first['route'] ?? $first['name'] ?? ''));
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            $candidate = trim((string) $first);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Sin ruta registrada';
    }
}
