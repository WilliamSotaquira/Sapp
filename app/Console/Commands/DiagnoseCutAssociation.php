<?php

namespace App\Console\Commands;

use App\Models\Cut;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseCutAssociation extends Command
{
    protected $signature = 'cuts:diagnose {--company= : Company ID to filter} {--cut= : Cut ID to diagnose}';
    protected $description = 'Diagnose why service requests are not being associated to a cut';

    public function handle(): int
    {
        $companyId = (int) $this->option('company');
        $cutId = (int) $this->option('cut');

        // List all cuts
        $cutsQuery = Cut::query()
            ->with('contract.company')
            ->withCount('serviceRequests')
            ->orderByDesc('start_date');

        if ($companyId) {
            $cutsQuery->whereHas('contract', fn($q) => $q->where('company_id', $companyId));
        }

        if ($cutId) {
            $cutsQuery->where('id', $cutId);
        }

        $cuts = $cutsQuery->get();

        if ($cuts->isEmpty()) {
            $this->error('No se encontraron cortes.');
            return 1;
        }

        foreach ($cuts as $cut) {
            $this->info("=== Corte #{$cut->id}: {$cut->name} ===");
            $this->info("  Contrato: #{$cut->contract_id} ({$cut->contract?->number}) - Empresa: {$cut->contract?->company?->name}");
            $this->info("  Rango: {$cut->start_date->format('Y-m-d H:i:s')} → {$cut->end_date->format('Y-m-d H:i:s')}");
            $this->info("  Solicitudes asociadas actualmente: {$cut->service_requests_count}");
            $this->newLine();

            [$start, $end] = $cut->getDateRangeForQuery();

            // Step 1: Find eligible requests (RESUELTA/CERRADA with dates)
            $eligibleCount = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->count();
            $this->info("  [1] Solicitudes elegibles (RESUELTA/CERRADA con fecha): {$eligibleCount}");

            // Step 2: Filter by company
            $companyFilterId = $companyId ?: (int) ($cut->contract?->company_id ?? 0);
            $afterCompanyFilter = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->where('company_id', $companyFilterId)
                ->count();
            $this->info("  [2] Filtro company_id={$companyFilterId}: {$afterCompanyFilter}");

            // Step 3: Filter by contract via relationship chain
            $afterContractFilter = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->where('company_id', $companyFilterId)
                ->whereHas('subService.service.family', function ($fq) use ($cut) {
                    $fq->where('contract_id', $cut->contract_id);
                })
                ->count();
            $this->info("  [3] Filtro contract_id={$cut->contract_id} (via subService.service.family): {$afterContractFilter}");

            // Step 3b: Same filter but including soft-deleted services/families
            $afterContractFilterWithTrashed = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->where('company_id', $companyFilterId)
                ->whereHas('subService', function ($subQ) use ($cut) {
                    $subQ->whereHas('service', function ($sQ) use ($cut) {
                        $sQ->withTrashed()->whereHas('family', function ($fQ) use ($cut) {
                            $fQ->withTrashed()->where('contract_id', $cut->contract_id);
                        });
                    });
                })
                ->count();

            if ($afterContractFilterWithTrashed !== $afterContractFilter) {
                $this->warn("  [3b] ¡PROBLEMA! Con withTrashed: {$afterContractFilterWithTrashed} (hay servicios/familias soft-deleted que bloquean la asociación)");
                $diff = $afterContractFilterWithTrashed - $afterContractFilter;
                $this->warn("       → {$diff} solicitud(es) están siendo excluidas por servicios/familias eliminados.");
            } else {
                $this->info("  [3b] Con withTrashed: {$afterContractFilterWithTrashed} (igual, no hay soft-deletes bloqueando)");
            }

            // Step 3c: Check if contract_id column exists on service_requests
            $hasContractIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('service_requests', 'contract_id');
            if ($hasContractIdColumn) {
                $directContractFilter = ServiceRequest::query()
                    ->eligibleForCutAssignment()
                    ->where('company_id', $companyFilterId)
                    ->where('contract_id', $cut->contract_id)
                    ->count();
                $this->info("  [3c] Filtro directo service_requests.contract_id={$cut->contract_id}: {$directContractFilter}");
            } else {
                $this->warn("  [3c] La columna contract_id NO existe en service_requests (migración no ejecutada)");
            }

            // Step 4: Date range filter
            $afterDateFilter = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->where('company_id', $companyFilterId)
                ->whereHas('subService.service.family', function ($fq) use ($cut) {
                    $fq->where('contract_id', $cut->contract_id);
                })
                ->where(function ($q) use ($start, $end) {
                    $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
                })
                ->count();
            $this->info("  [4] Filtro fecha (LEAST entre {$start->format('Y-m-d H:i:s')} y {$end->format('Y-m-d H:i:s')}): {$afterDateFilter}");

            // Step 4b: Date filter using broader date check
            $afterDateFilterBroad = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->where('company_id', $companyFilterId)
                ->whereHas('subService.service.family', function ($fq) use ($cut) {
                    $fq->where('contract_id', $cut->contract_id);
                })
                ->where(function ($q) use ($start, $end) {
                    $q->where(function ($inner) use ($start, $end) {
                        $inner->whereNotNull('closed_at')
                            ->whereBetween('closed_at', [$start, $end]);
                    })->orWhere(function ($inner) use ($start, $end) {
                        $inner->whereNotNull('resolved_at')
                            ->whereBetween('resolved_at', [$start, $end]);
                    });
                })
                ->count();
            $this->info("  [4b] Fecha con OR (closed_at O resolved_at en rango): {$afterDateFilterBroad}");

            if ($afterDateFilterBroad !== $afterDateFilter) {
                $this->warn("       → DIFERENCIA: LEAST da {$afterDateFilter}, OR da {$afterDateFilterBroad}");
            }

            // Show some sample requests that SHOULD be in this cut but aren't
            $associatedIds = $cut->serviceRequests()->pluck('service_requests.id')->all();

            $missingRequests = ServiceRequest::query()
                ->eligibleForCutAssignment()
                ->where('company_id', $companyFilterId)
                ->whereHas('subService.service.family', function ($fq) use ($cut) {
                    $fq->where('contract_id', $cut->contract_id);
                })
                ->where(function ($q) use ($start, $end) {
                    $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
                })
                ->whereNotIn('id', $associatedIds)
                ->take(10)
                ->get(['id', 'ticket_number', 'status', 'resolved_at', 'closed_at', 'sub_service_id', 'company_id']);

            if ($missingRequests->isNotEmpty()) {
                $this->newLine();
                $this->warn("  Solicitudes que DEBERÍAN estar asociadas pero NO lo están:");
                foreach ($missingRequests as $sr) {
                    $refDate = $sr->resolved_at ?? $sr->closed_at;
                    $this->warn("    - #{$sr->id} [{$sr->ticket_number}] Estado:{$sr->status} Ref:{$refDate} SubService:{$sr->sub_service_id}");

                    // Check why the relationship chain fails
                    $hasRelation = ServiceRequest::query()
                        ->where('id', $sr->id)
                        ->whereHas('subService.service.family', function ($fq) use ($cut) {
                            $fq->where('contract_id', $cut->contract_id);
                        })
                        ->exists();

                    if (!$hasRelation) {
                        // Try with trashed
                        $sub = $sr->subService;
                        $service = $sub ? \App\Models\Service::withTrashed()->find($sub->service_id) : null;
                        $family = $service ? \App\Models\ServiceFamily::withTrashed()->find($service->service_family_id) : null;

                        $this->error("      ↳ Cadena rota: SubService#{$sub?->id}→Service#{$service?->id}(deleted:{$service?->deleted_at})→Family#{$family?->id}(deleted:{$family?->deleted_at}, contract:{$family?->contract_id})");
                    }
                }
            }

            $this->newLine();
        }

        return 0;
    }
}
