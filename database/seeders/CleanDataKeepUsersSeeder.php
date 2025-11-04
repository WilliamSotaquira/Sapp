<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanDataKeepUsersSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Lista de tablas a limpiar (excluyendo users y migrations)
        $tablesToClean = [
            'posts',
            'comments',
            'categories',
            'products',
            'orders',
            'password_reset_tokens',
            'failed_jobs',
            'personal_access_tokens',
            'sessions',
            // Agrega aquí todas tus tablas excepto 'users'
        ];

        foreach ($tablesToClean as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("Tabla {$table} limpiada.");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info('Todos los datos excepto usuarios han sido eliminados.');
    }
}
