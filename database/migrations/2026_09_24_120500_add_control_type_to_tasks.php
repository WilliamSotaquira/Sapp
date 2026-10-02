<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía el ENUM tasks.type para admitir el tipo 'control'.
 *
 * Los tipos existentes ('impact', 'regular') son tareas de EJECUCIÓN que
 * realiza el técnico. 'control' es la tarea de VERIFICACIÓN/SEGUIMIENTO que
 * el líder crea automáticamente al delegar una solicitud (autoría dual): la
 * ejecuta el líder, no un técnico, y es la que obliga a verificar el contenido
 * publicado y cerrar la solicitud.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El ENUM nativo solo existe en MySQL/MariaDB; en SQLite (tests) la columna
        // es un varchar sin restricción que ya admite 'control', por lo que no hay
        // nada que redefinir. Igual que las migraciones hermanas que amplían enums.
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // MySQL: redefinir el ENUM para incluir 'control'.
        if (Schema::hasColumn('tasks', 'type')) {
            DB::statement("ALTER TABLE `tasks` MODIFY `type` ENUM('impact', 'regular', 'control') NOT NULL COMMENT 'IMPACT: 90min, REGULAR: 25min, CONTROL: verificación del líder'");
        }
    }

    public function down(): void
    {
        // Revertir cualquier tarea 'control' a 'regular' antes de reducir el enum,
        // para no perder filas por truncado.
        DB::table('tasks')->where('type', 'control')->update(['type' => 'regular']);

        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasColumn('tasks', 'type')) {
            DB::statement("ALTER TABLE `tasks` MODIFY `type` ENUM('impact', 'regular') NOT NULL COMMENT 'IMPACT: 90min, REGULAR: 25min'");
        }
    }
};
