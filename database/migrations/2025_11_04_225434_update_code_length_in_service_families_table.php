<?php
// database/migrations/xxxx_xx_xx_xxxxxx_update_code_length_in_service_families_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_families', function (Blueprint $table) {
            // Cambiar la longitud de la columna code a 50 caracteres
            $table->string('code', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_families', function (Blueprint $table) {
            // Revertir el cambio si es necesario
            $table->string('code', 20)->change();
        });
    }
};
