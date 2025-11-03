<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseCleanerSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Eliminar solo la tabla evidences si existe
        if (Schema::hasTable('evidences')) {
            Schema::drop('evidences');
            $this->command->info("Tabla evidences eliminada.");
        }

        // Reactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
