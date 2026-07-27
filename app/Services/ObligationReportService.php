<?php

namespace App\Services;

use App\Models\Cut;
use App\Models\ServiceFamily;
use App\Models\ServiceRequest;
use Illuminate\Support\Collection;

class ObligationReportService
{
    /**
     * Generate obligation report data for a cut.
     * Groups service requests by family and generates activity descriptions.
     */
    public function generateReport(Cut $cut, int $contractId): array
    {
        $families = ServiceFamily::where('contract_id', $contractId)
            ->where('is_active', true)
            ->ordered()
            ->get();

        $obligations = [];

        foreach ($families as $family) {
            $requests = $cut->serviceRequests()
                ->whereHas('subService.service', function ($q) use ($family) {
                    $q->where('service_family_id', $family->id);
                })
                ->whereIn('status', ['RESUELTA', 'CERRADA'])
                ->with(['subService.service'])
                ->get();

            $obligations[] = [
                'number' => (int) ($family->sort_order ?? 0),
                'family_id' => $family->id,
                'family_name' => $family->name,
                'description' => $family->description,
                'request_count' => $requests->count(),
                'requests' => $requests,
                'activity_text' => $this->generateActivityText($family, $requests, $cut),
                'percentage' => $requests->count() > 0 ? 100 : 0,
            ];
        }

        $totalRequests = collect($obligations)->sum('request_count');

        return [
            'obligations' => $obligations,
            'total_requests' => $totalRequests,
            'cut' => $cut,
            'period' => $cut->start_date->format('d/m/Y') . ' - ' . $cut->end_date->format('d/m/Y'),
        ];
    }

    /**
     * Auto-generate activity paragraph for an obligation based on its requests.
     */
    private function generateActivityText(ServiceFamily $family, Collection $requests, Cut $cut): string
    {
        if ($requests->isEmpty()) {
            return 'No se registraron solicitudes para esta obligación en el periodo.';
        }

        $count = $requests->count();
        $period = 'entre el ' . $cut->start_date->format('d/m/Y') . ' y el ' . $cut->end_date->format('d/m/Y');

        // Group by service
        $byService = $requests->groupBy(function ($sr) {
            return $sr->subService?->service?->name ?? 'Actividades generales';
        });

        // Build activities list from request titles
        $activities = $requests->pluck('title')
            ->unique()
            ->map(function ($title) {
                return mb_strtolower(trim($title));
            })
            ->values();

        // Limit to most relevant (max 8 activities listed)
        $listedActivities = $activities->take(8);
        $remaining = $activities->count() - 8;

        $activitiesText = $listedActivities->map(function ($item) {
            return $item;
        })->implode(', ');

        if ($remaining > 0) {
            $activitiesText .= ", entre otras ({$remaining} actividades adicionales)";
        }

        $text = "Se atendieron {$count} solicitud(es) en el periodo comprendido {$period}, incluyendo: {$activitiesText}.";

        // Add service breakdown if multiple services
        if ($byService->count() > 1) {
            $breakdown = $byService->map(function ($serviceRequests, $serviceName) {
                return $serviceName . ' (' . $serviceRequests->count() . ')';
            })->implode('; ');

            $text .= " Distribución por servicio: {$breakdown}.";
        }

        return $text;
    }

    /**
     * Detect orphan requests: resolved/closed in the cut period but not assigned to it.
     */
    public function detectOrphans(Cut $cut, int $contractId, int $companyId): Collection
    {
        [$start, $end] = $cut->getDateRangeForQuery();

        $query = ServiceRequest::where('company_id', $companyId)
            ->whereIn('status', ['RESUELTA', 'CERRADA'])
            ->whereHas('subService.service.family', function ($q) use ($contractId) {
                $q->where('contract_id', $contractId);
            })
            ->whereDoesntHave('cuts', function ($q) use ($cut) {
                $q->where('cuts.id', $cut->id);
            });

        // Open cuts capture everything from start_date onward
        if ($cut->isOpen()) {
            $query->where(function ($q) use ($start) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) >= ?', [$start]);
            });
        } else {
            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
            });
        }

        return $query->get(['id', 'ticket_number', 'title', 'status', 'resolved_at', 'closed_at']);
    }

    /**
     * Validate cut readiness for closure.
     */
    public function validateReadiness(Cut $cut, int $contractId, int $companyId): array
    {
        $orphans = $this->detectOrphans($cut, $contractId, $companyId);
        $totalRequests = $cut->serviceRequests()->count();

        $families = ServiceFamily::where('contract_id', $contractId)
            ->where('is_active', true)
            ->ordered()
            ->get();

        $emptyFamilies = [];
        foreach ($families as $family) {
            $count = $cut->serviceRequests()
                ->whereHas('subService.service', function ($q) use ($family) {
                    $q->where('service_family_id', $family->id);
                })
                ->count();

            if ($count === 0) {
                $emptyFamilies[] = $family->name;
            }
        }

        $issues = [];
        if ($orphans->isNotEmpty()) {
            $issues[] = [
                'type' => 'orphans',
                'severity' => 'warning',
                'message' => "{$orphans->count()} solicitud(es) resuelta(s) en el periodo sin asignar al corte.",
                'data' => $orphans,
            ];
        }
        if (!empty($emptyFamilies)) {
            $issues[] = [
                'type' => 'empty_families',
                'severity' => 'info',
                'message' => count($emptyFamilies) . " familia(s) sin solicitudes: " . implode(', ', $emptyFamilies),
                'data' => $emptyFamilies,
            ];
        }
        if ($totalRequests === 0) {
            $issues[] = [
                'type' => 'no_requests',
                'severity' => 'error',
                'message' => 'El corte no tiene solicitudes asociadas.',
                'data' => null,
            ];
        }

        return [
            'ready' => empty(array_filter($issues, fn($i) => $i['severity'] === 'error')),
            'total_requests' => $totalRequests,
            'orphans_count' => $orphans->count(),
            'empty_families_count' => count($emptyFamilies),
            'issues' => $issues,
        ];
    }
}
