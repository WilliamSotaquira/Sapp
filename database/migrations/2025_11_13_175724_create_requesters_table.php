<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_requesters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requesters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre completo del solicitante
            $table->string('email')->nullable(); // Email (opcional)
            $table->string('phone')->nullable(); // Teléfono (opcional)
            $table->string('department')->nullable(); // Departamento/Área
            $table->string('position')->nullable(); // Cargo/Puesto
            $table->boolean('is_active')->default(true); // Si está activo
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsqueda eficiente
            $table->index('name');
            $table->index('email');
            $table->index('department');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('requesters');
    }
};
