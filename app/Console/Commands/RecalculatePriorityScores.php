<?php

namespace App\Console\Commands;

use App\Services\PriorityScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecalculatePriorityScores extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'service-requests:recalculate-priorities
                            {--cut-date= : Fecha de corte para calcular antigüedad (YYYY-MM-DD, default: hoy)}
                            {--company= : ID de empresa específica (opcional)}';

    /**
     * The console command description.
     */
    protected $description = 'Recalcular puntajes de prioridad (P0-P4) para todas las solicitudes abiertas basándose en criticidad, complejidad, antigüedad y desconfianza';

    /**
     * Execute the console command.
     */
    public function handle(PriorityScoringService $scoringService): int
    {
        $cutDate = $this->option('cut-date')
            ? Carbon::parse($this->option('cut-date'))
            : Carbon::now();

        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        $this->info("🔄 Recalculando prioridades...");
        $this->info("   Fecha de corte: {$cutDate->format('Y-m-d')}");

        if ($companyId) {
            $this->info("   Empresa: #{$companyId}");
        }

        $this->newLine();

        $count = $scoringService->recalculateAll($cutDate, $companyId);

        $this->info("✅ Se recalcularon {$count} solicitudes.");
        $this->newLine();

        // Mostrar resumen por nivel
        $this->table(
            ['Nivel', 'Descripción', 'Cantidad'],
            collect(['P0', 'P1', 'P2', 'P3', 'P4'])->map(function ($level) use ($companyId) {
                $query = \App\Models\ServiceRequest::withoutGlobalScope('workspace')
                    ->where('priority_level', $level)
                    ->whereNotIn('status', ['CERRADA', 'CANCELADA', 'RECHAZADA']);

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }

                return [
                    $level,
                    PriorityScoringService::PRIORITY_LABELS[$level] ?? '',
                    $query->count(),
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }
}
