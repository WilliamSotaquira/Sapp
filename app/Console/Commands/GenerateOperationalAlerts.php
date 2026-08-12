<?php

namespace App\Console\Commands;

use App\Services\OperationalAlertService;
use Illuminate\Console\Command;

class GenerateOperationalAlerts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'alerts:generate
                            {--company= : ID de la empresa/workspace a evaluar (opcional, default: todas)}
                            {--dry-run : Ejecutar en modo simulación sin crear alertas}
                            {--verbose-output : Mostrar detalle de cada alerta generada}';

    /**
     * The console command description.
     */
    protected $description = 'Evaluar solicitudes y tareas activas para generar alertas operativas según umbrales configurados';

    /**
     * Execute the console command.
     */
    public function handle(OperationalAlertService $alertService): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   Motor de Alertas Operativas                   ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->info('');

        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('⚡ Modo simulación: las alertas generadas se mostrarán pero no se persistirán.');
            $this->info('   (Nota: esta ejecución SÍ genera alertas. Use para validar la primera ejecución.)');
            $this->info('');
        }

        if ($companyId) {
            $this->info("📍 Evaluando empresa ID: {$companyId}");
        } else {
            $this->info('📍 Evaluando todas las empresas');
        }

        $this->info('⏱️  Iniciando evaluación...');
        $this->info('');

        // Ejecutar evaluación
        $result = $alertService->evaluate($companyId);

        // Mostrar resultados
        if ($result['status'] === 'disabled') {
            $this->warn('⚠️  ' . $result['message']);
            return self::SUCCESS;
        }

        if ($result['status'] === 'error') {
            $this->error('❌ Error: ' . $result['message']);
            return self::FAILURE;
        }

        // Resumen exitoso
        $this->info('✅ Evaluación completada');
        $this->info('');

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Duración', ($result['duration_seconds'] ?? 0) . ' seg'],
                ['Alertas generadas', $result['alerts_generated']],
                ['Alertas resueltas (auto)', $result['alerts_resolved']],
            ]
        );

        // Detalle por tipo
        if (!empty($result['summary'])) {
            $this->info('');
            $this->info('📊 Detalle por tipo de alerta:');

            $typeRows = [];
            foreach ($result['summary'] as $type => $count) {
                $label = \App\Models\OperationalAlert::$alertTypes[$type]['label'] ?? $type;
                $typeRows[] = [$label, $count];
            }

            $this->table(['Tipo', 'Cantidad'], $typeRows);
        }

        // Mostrar resumen general de alertas activas
        $activeSummary = $alertService->getActiveSummary($companyId);

        $this->info('');
        $this->info('📋 Estado actual de alertas activas:');
        $this->table(
            ['Severidad', 'Cantidad'],
            [
                ['🔴 Crítica', $activeSummary['by_severity']['critica']],
                ['🟠 Alta', $activeSummary['by_severity']['alta']],
                ['🟡 Media', $activeSummary['by_severity']['media']],
                ['🔵 Baja', $activeSummary['by_severity']['baja']],
                ['─────────', '───'],
                ['Total activas', $activeSummary['total']],
                ['Sin leer', $activeSummary['unread']],
            ]
        );

        $this->info('');

        return self::SUCCESS;
    }
}
