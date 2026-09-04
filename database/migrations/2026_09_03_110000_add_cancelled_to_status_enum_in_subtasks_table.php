<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega el valor 'cancelled' al ENUM de estado de subtareas para que,
     * al cancelar una tarea (por rechazo, no viabilidad, cierre por
     * vencimiento o cancelación de la solicitud), sus subtareas no
     * completadas puedan quedar en un estado final coherente.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE subtasks MODIFY COLUMN status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Antes de revertir, normalizar los 'cancelled' existentes a 'pending'
        // para no perder filas por un valor fuera del ENUM reducido.
        DB::statement("UPDATE subtasks SET status = 'pending' WHERE status = 'cancelled'");

        DB::statement("ALTER TABLE subtasks MODIFY COLUMN status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending'");
    }
};
