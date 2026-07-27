<?php

namespace App\Console\Commands;

use App\Models\Cut;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckCutHealth extends Command
{
    protected $signature = 'cuts:health-check {--fix : Attempt to re-sync cuts with 0 requests}';
    protected $description = 'Detect cuts with potential issues: 0 associated requests, short durations, or date gaps';

    public function handle(): int
    {
        $issues = [];
        $fixed = 0;

        $cuts = Cut::query()
            ->with('contract.company')
            ->withCount('serviceRequests')
            ->where('end_date', '>=', now()->subMonths(3))
            ->orderBy('contract_id')
            ->orderBy('start_date')
            ->get();

        foreach ($cuts as $cut) {
            $companyName = $cut->contract?->company?->name ?? 'Sin empresa';
            $durationHours = $cut->start_date->diffInHours($cut->end_date);
            $label = "Corte #{$cut->id} \"{$cut->name}\" ({$companyName})";

            // Check 1: Duration too short (< 24 hours)
            if ($durationHours < 24) {
                $issues[] = [
                    'cut_id' => $cut->id,
                    'type' => 'short_duration',
                    'message' => "{$label}: duración de solo {$durationHours}h ({$cut->start_date->format('Y-m-d H:i')} → {$cut->end_date->format('Y-m-d H:i')})",
                ];
            }

            // Check 2: Zero requests but the range should have some
            if ($cut->service_requests_count === 0) {
                $contractId = $cut->contract_id;
                $companyId = (int) ($cut->contract?->company_id ?? 0);
                [$start, $end] = $cut->getDateRangeForQuery();

                $potentialCount = ServiceRequest::query()
                    ->eligibleForCutAssignment()
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->when($contractId, function ($q) use ($contractId) {
                        $q->whereHas('subService.service.family', fn($fq) => $fq->where('contract_id', $contractId));
                    })
                    ->where(function ($q) use ($start, $end) {
                        $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
                    })
                    ->count();

                if ($potentialCount > 0) {
                    $issues[] = [
                        'cut_id' => $cut->id,
                        'type' => 'missing_requests',
                        'message' => "{$label}: tiene 0 solicitudes pero hay {$potentialCount} elegible(s) en su rango. Requiere re-sincronización.",
                        'fixable' => true,
                    ];

                    if ($this->option('fix')) {
                        $this->resyncCut($cut, $start, $end, $companyId, $contractId);
                        $fixed++;
                        $this->info("  ✓ Re-sincronizado: {$potentialCount} solicitud(es) asociadas.");
                    }
                } elseif ($cut->end_date->lt(now())) {
                    // Cut is in the past and has no requests at all
                    $issues[] = [
                        'cut_id' => $cut->id,
                        'type' => 'empty_past_cut',
                        'message' => "{$label}: corte pasado sin solicitudes asociadas (posible corte sin actividad o con fechas incorrectas).",
                    ];
                }
            }

            // Check 3: end_date is in the past but very close to start_date (possible mis-configuration)
            if ($cut->end_date->lt(now()) && $durationHours < 168 && $cut->service_requests_count === 0) {
                // Already captured by short_duration or missing_requests above, skip duplicate
            }
        }

        // Check 4: Overlapping cuts within same contract
        $cutsByContract = $cuts->groupBy('contract_id');
        foreach ($cutsByContract as $contractId => $contractCuts) {
            $sorted = $contractCuts->sortBy('start_date')->values();
            for ($i = 0; $i < $sorted->count() - 1; $i++) {
                $current = $sorted[$i];
                $next = $sorted[$i + 1];

                if ($current->end_date->gte($next->start_date)) {
                    $overlapHours = (int) $next->start_date->diffInHours($current->end_date);
                    $companyName = $current->contract?->company?->name ?? 'Sin empresa';
                    $issues[] = [
                        'cut_id' => $current->id,
                        'type' => 'overlap',
                        'message' => "Solapamiento ({$companyName}): Corte #{$current->id} \"{$current->name}\" se solapa con #{$next->id} \"{$next->name}\" por {$overlapHours}h.",
                    ];
                }
            }
        }

        // Output results
        if (empty($issues)) {
            $this->info('✓ Todos los cortes están saludables. Sin problemas detectados.');
            Log::info('[cuts:health-check] Todos los cortes están saludables.');
            return 0;
        }

        $this->warn('Problemas detectados: ' . count($issues));
        $this->newLine();

        $grouped = collect($issues)->groupBy('type');

        foreach ($grouped as $type => $typeIssues) {
            $typeLabel = match ($type) {
                'short_duration' => 'Duración insuficiente',
                'missing_requests' => 'Solicitudes no asociadas',
                'empty_past_cut' => 'Cortes vacíos (pasados)',
                'overlap' => 'Solapamientos',
                default => $type,
            };

            $this->info("── {$typeLabel} ({$typeIssues->count()}) ──");
            foreach ($typeIssues as $issue) {
                $this->line("  • {$issue['message']}");
            }
            $this->newLine();
        }

        if ($fixed > 0) {
            $this->info("Se re-sincronizaron {$fixed} corte(s).");
        } elseif ($grouped->has('missing_requests')) {
            $this->comment('Usa --fix para intentar re-sincronizar los cortes con solicitudes faltantes.');
        }

        // Log for monitoring
        Log::warning('[cuts:health-check] Problemas detectados: ' . count($issues), [
            'issues' => collect($issues)->map(fn($i) => $i['message'])->all(),
        ]);

        return 1;
    }

    private function resyncCut(Cut $cut, Carbon $start, Carbon $end, int $companyId, int $contractId): void
    {
        $requestIds = ServiceRequest::query()
            ->eligibleForCutAssignment()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($contractId, function ($q) use ($contractId) {
                $q->whereHas('subService.service.family', fn($fq) => $fq->where('contract_id', $contractId));
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereRaw('LEAST(COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)) BETWEEN ? AND ?', [$start, $end]);
            })
            ->pluck('id')
            ->all();

        $cut->serviceRequests()->sync($requestIds);
    }
}
