<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabaseCommand extends Command
{
    protected $signature = 'db:clean';
    protected $description = 'Clean custom tables from database';

    public function handle()
    {
        if (!$this->confirm('¿Estás seguro de que quieres eliminar todas las tablas personalizadas?')) {
            $this->info('Operación cancelada.');
            return;
        }

        // Desactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = ['evidences', 'requirements', 'projects', 'reporters', 'classifications', 'alerts'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->info("✅ Tabla {$table} eliminada.");
            } else {
                $this->info("ℹ️  Tabla {$table} no existe.");
            }
        }

        // Reactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('🎉 Base de datos limpiada exitosamente.');
    }
}
