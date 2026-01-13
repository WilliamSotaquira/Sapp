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

        // MySQL: extender el enum manteniendo valores existentes (p.ej. 'updated').
        // Si omitimos algún valor que ya existe en datos, MySQL lanza warning 1265 (Data truncated) y la migración falla.
        $existingValues = [];
        if (is_string($type)) {
            preg_match_all("/'([^']*)'/", $type, $matches);
            $existingValues = $matches[1] ?? [];
        }

        $existingValues = array_values(array_unique(array_filter($existingValues, fn($v) => $v !== '')));
        if (!in_array('deleted', $existingValues, true)) {
            $existingValues[] = 'deleted';
        }

        // Fallback por seguridad si no pudimos leer los valores del enum.
        if (count($existingValues) === 1 && $existingValues[0] === 'deleted') {
            $existingValues = ['created', 'assigned', 'started', 'paused', 'resumed', 'completed', 'cancelled', 'rescheduled', 'blocked', 'unblocked', 'updated', 'deleted'];
        }

        $enumSql = implode(',', array_map(function ($value) {
            return "'" . str_replace("'", "\\'", $value) . "'";
        }, $existingValues));

        DB::statement("ALTER TABLE `task_histories` MODIFY `action` ENUM({$enumSql}) NOT NULL");
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
