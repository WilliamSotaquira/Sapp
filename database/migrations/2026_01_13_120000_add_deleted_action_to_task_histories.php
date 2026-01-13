<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('task_histories')) {
            return;
        }

        // Solo MySQL: usamos SHOW COLUMNS + ALTER TABLE MODIFY ENUM.
        // En SQLite (tests) y otros drivers, no hacemos nada para evitar fallos.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Detectar si el enum ya incluye "deleted" para evitar ALTER repetidos.
        $column = DB::selectOne("SHOW COLUMNS FROM `task_histories` WHERE Field = 'action'");
        $type = is_object($column) ? ($column->Type ?? null) : null;

        if (is_string($type) && str_contains($type, "'deleted'")) {
            return;
        }

        // MySQL: extender el enum manteniendo valores existentes.
        DB::statement(
            "ALTER TABLE `task_histories` MODIFY `action` ENUM(".
            "'created','assigned','started','paused','resumed','completed','cancelled','rescheduled','blocked','unblocked','deleted'".
            ") NOT NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('task_histories')) {
            return;
        }

        // No quitamos 'deleted' para evitar fallos si ya existen registros con ese valor.
        // En caso de requerir rollback estricto, primero habría que migrar esos registros.
    }
};
