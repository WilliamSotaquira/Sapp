<?php

namespace App\Console\Commands;

use App\Services\EvidenceOrganizerService;
use Illuminate\Console\Command;

class CleanOrphanedBackups extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evidence:clean-backups';

    /**
     * The console command description.
     */
    protected $description = 'Eliminar backups huérfanos de evidencia con más de 24 horas de antigüedad';

    /**
     * Execute the console command.
     */
    public function handle(EvidenceOrganizerService $service): int
    {
        $this->info('🔄 Limpiando backups huérfanos de evidencia...');

        $cleanedCount = $service->cleanOrphanedBackups();

        $this->info("✅ Se eliminaron {$cleanedCount} archivo(s) de backup huérfano(s).");

        return Command::SUCCESS;
    }
}
