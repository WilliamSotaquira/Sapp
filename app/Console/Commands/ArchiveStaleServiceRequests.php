<?php

namespace App\Console\Commands;

use App\Models\OperationalAlert;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ArchiveStaleServiceRequests extends Command
{
    protected $signature = 'service-requests:archive-stale
                            {--days=90 : Días sin actualización para considerar inactiva}
                            {--dry-run : Simular sin ejecutar cambios}
                            {--force : Archivar sin confirmación interactiva}';

    protected $description = 'Archiva solicitudes abiertas sin actividad por más de N días y genera alertas informativas';

    /**
     * Estados que se consideran "abiertos" (candidatas a archivarse).
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
        $force = (bool) $this->option('force');
        $cutoffDate = Carbon::now()->subDays($days);
        $now = Carbon::now();

        $this->info("Buscando solicitudes abiertas sin actividad desde antes del {$cutoffDate->format('Y-m-d')}...");

        $query = ServiceRequest::withoutGlobalScope('workspace')
            ->whereIn('status', self::OPEN_STATUSES)
            ->where('updated_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No se encontraron solicitudes inactivas para archivar.');
            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$count} solicitudes sin actividad por más de {$days} días.");

        if ($dryRun) {
            $this->table(
                ['Ticket', 'Estado', 'Última actividad', 'Días inactiva'],
                $query->limit(50)->get(['ticket_number', 'status', 'updated_at'])->map(fn ($sr) => [
                    $sr->ticket_number,
                    $sr->status,
                    $sr->updated_at->format('Y-m-d'),
                    (int) $sr->updated_at->diffInDays(now()),
                ])
            );

            if ($count > 50) {
                $this->line("... y " . ($count - 50) . " más.");
            }

            $this->info('[DRY-RUN] No se realizaron cambios.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm("¿Deseas archivar {$count} solicitudes por inactividad?")) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $archived = 0;
        $alerted = 0;
        $errors = 0;

        $query->chunkById(100, function ($serviceRequests) use (&$archived, &$alerted, &$errors, $now, $days) {
            foreach ($serviceRequests as $sr) {
                try {
                    // 1. Generar alerta informativa antes de archivar
                    $daysInactive = (int) $sr->updated_at->diffInDays($now);

                    OperationalAlert::createIfNotExists([
                        'alertable_type' => ServiceRequest::class,
                        'alertable_id' => $sr->id,
                        'alert_type' => OperationalAlert::TYPE_STALE_REQUEST,
                        'severity' => OperationalAlert::SEVERITY_MEDIUM,
                        'title' => "Solicitud archivada por inactividad ({$daysInactive} días)",
                        'message' => "La solicitud {$sr->ticket_number} fue archivada automáticamente por no registrar actividad en {$daysInactive} días. Estado anterior: {$sr->status}.",
                        'metadata' => [
                            'previous_status' => $sr->status,
                            'days_inactive' => $daysInactive,
                            'archived_at' => $now->toISOString(),
                            'ticket_number' => $sr->ticket_number,
                        ],
                        'alert_at' => $now,
                    ]);
                    $alerted++;

                    // 2. Archivar la solicitud (update directo para evitar validaciones de workflow)
                    ServiceRequest::withoutGlobalScope('workspace')
                        ->where('id', $sr->id)
                        ->update([
                            'status' => ServiceRequest::STATUS_ARCHIVED,
                            'resolution_notes' => trim(
                                ($sr->resolution_notes ? $sr->resolution_notes . "\n\n" : '') .
                                "[Archivado automático] Sin actividad por {$daysInactive} días. Estado anterior: {$sr->status}. Fecha: {$now->format('Y-m-d H:i')}"
                            ),
                        ]);

                    $archived++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Error en {$sr->ticket_number}: {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->info("Resultado:");
        $this->line("  • {$archived} solicitudes archivadas");
        $this->line("  • {$alerted} alertas generadas");

        if ($errors > 0) {
            $this->warn("  • {$errors} errores");
        }

        return self::SUCCESS;
    }
}
