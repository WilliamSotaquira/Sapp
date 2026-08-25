<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseExpiredServiceRequests extends Command
{
    protected $signature = 'service-requests:close-expired
                            {--days=120 : Días de antigüedad para considerar vencida}
                            {--dry-run : Simular sin ejecutar cambios}';

    protected $description = 'Cierra por vencimiento las solicitudes abiertas con más de N días de antigüedad';

    /**
     * Estados que se consideran "abiertos" (no terminales).
     */
    private const OPEN_STATUSES = [
        'PENDIENTE',
        'ACEPTADA',
        'EN_PROCESO',
        'PAUSADA',
        'REABIERTO',
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Buscando solicitudes abiertas creadas antes del {$cutoffDate->format('Y-m-d')}...");

        $query = ServiceRequest::withoutGlobalScope('workspace')
            ->whereIn('status', self::OPEN_STATUSES)
            ->where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No se encontraron solicitudes para cerrar por vencimiento.');
            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$count} solicitudes abiertas con más de {$days} días.");

        if ($dryRun) {
            $this->table(
                ['Ticket', 'Estado', 'Creada', 'Días'],
                $query->limit(50)->get(['ticket_number', 'status', 'created_at'])->map(fn ($sr) => [
                    $sr->ticket_number,
                    $sr->status,
                    $sr->created_at->format('Y-m-d'),
                    $sr->created_at->diffInDays(now()),
                ])
            );

            if ($count > 50) {
                $this->line("... y " . ($count - 50) . " más.");
            }

            $this->info('[DRY-RUN] No se realizaron cambios.');
            return self::SUCCESS;
        }

        if (!$this->confirm("¿Deseas cerrar {$count} solicitudes por vencimiento?")) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $closed = 0;
        $errors = 0;
        $now = Carbon::now();

        $query->chunkById(100, function ($serviceRequests) use (&$closed, &$errors, $now, $days) {
            foreach ($serviceRequests as $sr) {
                try {
                    // Usar update directo en BD para evitar validaciones de workflow
                    // (cierre administrativo por vencimiento no requiere técnico asignado)
                    $updateData = [
                        'status' => ServiceRequest::STATUS_CLOSED,
                        'closed_at' => $now,
                        'resolution_notes' => trim(
                            ($sr->resolution_notes ? $sr->resolution_notes . "\n\n" : '') .
                            "[Cierre automático] Cerrada por vencimiento ({$days} días sin actividad). Fecha: {$now->format('Y-m-d H:i')}"
                        ),
                    ];

                    // Si no tiene resolved_at, marcar también como resuelta
                    if (!$sr->resolved_at) {
                        $updateData['resolved_at'] = $now;
                    }

                    ServiceRequest::withoutGlobalScope('workspace')
                        ->where('id', $sr->id)
                        ->update($updateData);

                    $closed++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Error en {$sr->ticket_number}: {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->info("Resultado: {$closed} solicitudes cerradas por vencimiento.");

        if ($errors > 0) {
            $this->warn("{$errors} solicitudes no pudieron cerrarse.");
        }

        return self::SUCCESS;
    }
}
