<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para agregar RECHAZADA
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA','RESUELTA','CERRADA','CANCELADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir quitando RECHAZADA del ENUM
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA','RESUELTA','CERRADA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE'");
    }
};
