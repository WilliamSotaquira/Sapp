<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campos de priorización avanzada a service_requests.
     *
     * Sistema de puntaje combinado:
     * - Criticidad (BAJA, MEDIA, ALTA, CRITICA)
     * - Complejidad (BAJA, MEDIA, ALTA)
     * - Antigüedad (calculada dinámicamente desde created_at vs fecha de corte)
     * - Desconfianza (factor de incertidumbre del origen/canal)
     * - Puntaje total → Clasificación P0-P4
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Nivel de complejidad de la solicitud
            $table->enum('complexity_level', ['BAJA', 'MEDIA', 'ALTA'])->default('MEDIA')->after('criticality_level');

            // Factor de desconfianza (1-5): qué tan incierto es el origen/estado
            $table->tinyInteger('distrust_factor')->default(1)->after('complexity_level');

            // Puntaje de prioridad calculado (se actualiza al guardar/recalcular)
            $table->integer('priority_score')->default(0)->after('distrust_factor');

            // Clasificación de prioridad resultante (P0-P4)
            $table->enum('priority_level', ['P0', 'P1', 'P2', 'P3', 'P4'])->default('P3')->after('priority_score');

            // Clasificación de antigüedad (calculada dinámicamente)
            $table->enum('antiquity_class', ['MUY_RECIENTE', 'RECIENTE', 'MEDIA', 'ANTIGUA', 'MUY_ANTIGUA'])->nullable()->after('priority_level');

            // Número de correos en el hilo (para puntaje adicional)
            $table->integer('thread_count')->default(1)->after('antiquity_class');

            // Fecha de corte para cálculo de antigüedad
            $table->date('cut_date')->nullable()->after('thread_count');

            // Índices para consultas de priorización
            $table->index('priority_level');
            $table->index('priority_score');
            $table->index('complexity_level');
            $table->index('antiquity_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['priority_level']);
            $table->dropIndex(['priority_score']);
            $table->dropIndex(['complexity_level']);
            $table->dropIndex(['antiquity_class']);

            $table->dropColumn([
                'complexity_level',
                'distrust_factor',
                'priority_score',
                'priority_level',
                'antiquity_class',
                'thread_count',
                'cut_date',
            ]);
        });
    }
};
