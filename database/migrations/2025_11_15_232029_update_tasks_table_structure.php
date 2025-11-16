<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Agregar columnas que faltan para compatibilidad con las vistas
            $table->time('scheduled_start_time')->nullable()->after('scheduled_date');
            $table->decimal('estimated_hours', 4, 1)->nullable()->after('scheduled_start_time');
            $table->decimal('actual_hours', 4, 1)->nullable()->after('estimated_hours');

            // Actualizar enum de prioridad para incluir 'urgent' y 'low'
            $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_start_time',
                'estimated_hours',
                'actual_hours'
            ]);
        });
    }
};
