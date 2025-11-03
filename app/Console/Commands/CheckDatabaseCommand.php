<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabaseCommand extends Command
{
    protected $signature = 'db:check';
    protected $description = 'Check database tables and structure';

    public function handle()
    {
        $this->info('🔍 Verificando base de datos SDM...');

        $tables = [
            'requirements',
            'evidences',
            'projects',
            'reporters',
            'classifications',
            'alerts'
        ];

        $this->line('');
        $this->info('📊 Tablas en la base de datos:');
        $this->line('');

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    $count = DB::table($table)->count();
                    $this->info("   ✅ {$table} - {$count} registros");

                    // Mostrar algunas columnas
                    $columns = Schema::getColumnListing($table);
                    $this->comment("      Columnas: " . implode(', ', array_slice($columns, 0, 5)) . (count($columns) > 5 ? '...' : ''));

                } catch (\Exception $e) {
                    $this->error("   ❌ {$table} - Error: " . $e->getMessage());
                }
            } else {
                $this->error("   ❌ {$table} - NO EXISTE");
            }
        }

        $this->line('');

        // Verificar conexión
        try {
            DB::connection()->getPdo();
            $this->info('✅ Conexión a la base de datos: OK');
            $this->info('✅ Base de datos: ' . DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->error('❌ Error de conexión: ' . $e->getMessage());
        }

        $this->line('');
        $this->info('🎯 Estado de migraciones:');
        $this->call('migrate:status');
    }
}
