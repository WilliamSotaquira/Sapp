<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Campos dedicados para registrar la finalización por NO VIABILIDAD.
     * A diferencia del rechazo o la cancelación, esta finalización representa
     * gestión realizada (se hizo la validación/concepto) que no se completó
     * porque la solicitud no cumple las características necesarias.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Motivo por el que se determinó la no viabilidad (concepto técnico)
            $table->text('non_viable_reason')->nullable()->after('rejected_by');

            // Fecha y hora en que se finalizó por no viabilidad
            $table->timestamp('non_viable_at')->nullable()->after('non_viable_reason');

            // Usuario que finalizó la solicitud por no viabilidad
            $table->foreignId('non_viable_by')
                ->nullable()
                ->after('non_viable_at')
                ->constrained('users')
                ->onDelete('set null');

            // Índices para métricas por período
            $table->index('non_viable_at');
            $table->index('non_viable_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['non_viable_by']);
            $table->dropIndex(['non_viable_at']);
            $table->dropIndex(['non_viable_by']);
            $table->dropColumn([
                'non_viable_reason',
                'non_viable_at',
                'non_viable_by',
            ]);
        });
    }
};
