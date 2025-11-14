<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar a VARCHAR temporalmente para limpiar los datos
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN criticality_level VARCHAR(15) NOT NULL DEFAULT 'MEDIA'");

        // Actualizar cualquier valor vacío
        DB::table('service_requests')
            ->where('criticality_level', '')
            ->update(['criticality_level' => 'MEDIA']);

        // Finalmente cambiar a ENUM con los valores correctos
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN criticality_level ENUM('BAJA', 'MEDIA', 'ALTA', 'URGENTE', 'CRITICA') NOT NULL DEFAULT 'MEDIA'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN criticality_level ENUM('BAJA', 'MEDIA', 'ALTA', 'CRITICA') NOT NULL DEFAULT 'MEDIA'");
    }
};
