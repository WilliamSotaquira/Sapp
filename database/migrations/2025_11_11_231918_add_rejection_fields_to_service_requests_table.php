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
        Schema::table('service_requests', function (Blueprint $table) {
            // Campo para el motivo del rechazo
            $table->text('rejection_reason')->nullable()->after('resolution_notes');

            // Fecha y hora del rechazo
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');

            // Usuario que rechazó la solicitud
            $table->foreignId('rejected_by')
                  ->nullable()
                  ->after('rejected_at')
                  ->constrained('users')
                  ->onDelete('set null');

            // Índices para mejor performance
            $table->index('rejected_at');
            $table->index('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Eliminar la foreign key primero
            $table->dropForeign(['rejected_by']);

            // Eliminar los índices
            $table->dropIndex(['rejected_at']);
            $table->dropIndex(['rejected_by']);

            // Eliminar las columnas
            $table->dropColumn([
                'rejection_reason',
                'rejected_at',
                'rejected_by'
            ]);
        });
    }
};
