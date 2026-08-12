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
        Schema::create('operational_alerts', function (Blueprint $table) {
            $table->id();

            // Referencia polimórfica: puede apuntar a ServiceRequest o Task
            $table->morphs('alertable');

            // Tipo de alerta
            $table->string('alert_type', 50);
            // Severidad: critica, alta, media, baja
            $table->string('severity', 20)->default('media');

            // Información de la alerta
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();

            // Estado de la alerta
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->boolean('is_resolved')->default(false);

            // Fechas de gestión
            $table->timestamp('alert_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Quién la resolvió/descartó
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            // Notas de resolución
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['alert_type', 'is_resolved'], 'op_alerts_type_resolved');
            $table->index(['severity', 'is_resolved'], 'op_alerts_severity_resolved');
            $table->index(['alertable_type', 'alertable_id', 'alert_type'], 'op_alerts_poly_type');
            $table->index(['is_resolved', 'is_dismissed', 'alert_at'], 'op_alerts_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_alerts');
    }
};
