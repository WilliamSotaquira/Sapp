<?php
// database/migrations/2025_11_04_create_service_request_evidences_table_final.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Solo crear si no existe
        if (!Schema::hasTable('service_request_evidences')) {
            Schema::create('service_request_evidences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('evidence_data')->nullable();
                $table->string('evidence_type'); // PASO_A_PASO, ARCHIVO, COMENTARIO, SISTEMA
                $table->integer('step_number')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_original_name')->nullable();
                $table->string('file_mime_type')->nullable();
                $table->integer('file_size')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Índices
                $table->index(['service_request_id', 'evidence_type']);
                $table->index(['service_request_id', 'step_number']);
            });

            // Mensaje de confirmación
            echo "Tabla service_request_evidences creada exitosamente.\n";
        } else {
            echo "La tabla service_request_evidences ya existe.\n";
        }
    }

    public function down()
    {
        Schema::dropIfExists('service_request_evidences');
    }
};
