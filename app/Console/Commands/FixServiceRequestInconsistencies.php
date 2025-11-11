<?php
// app/Console/Commands/FixServiceRequestInconsistencies.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceRequest;

class FixServiceRequestInconsistencies extends Command
{
    protected $signature = 'service-requests:fix-inconsistencies';
    protected $description = 'Corrige inconsistencias en el flujo de trabajo de service requests';

    public function handle()
    {
        $inconsistentRequests = ServiceRequest::withInconsistencies()->get();

        $this->info("Encontradas {$inconsistentRequests->count()} solicitudes inconsistentes.");

        $fixedCount = 0;
        foreach ($inconsistentRequests as $request) {
            $this->warn("Corrigiendo solicitud #{$request->ticket_number}...");

            if ($request->fixInconsistency()) {
                $this->info("  ✅ Corregida: {$request->ticket_number}");
                $fixedCount++;
            } else {
                $this->error("  ❌ Error al corregir: {$request->ticket_number}");
            }
        }

        $this->info("✅ {$fixedCount} solicitudes han sido corregidas.");

        if ($fixedCount > 0) {
            \Log::info("Comando de corrección ejecutado: {$fixedCount} solicitudes inconsistentes corregidas.");
        }
    }
}
