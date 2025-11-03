<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('service_requests', function (Blueprint $table) {
        $table->id();
        $table->string('ticket_number')->unique();
        $table->foreignId('sla_id')->constrained('service_level_agreements');
        $table->foreignId('sub_service_id')->constrained();
        $table->foreignId('requested_by')->constrained('users');
        $table->foreignId('assigned_to')->nullable()->constrained('users');
        $table->string('title');
        $table->text('description');
        $table->enum('criticality_level', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA']);
        $table->enum('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'RESUELTA', 'CERRADA', 'CANCELADA'])->default('PENDIENTE');

        $table->timestamp('acceptance_deadline')->nullable();
        $table->timestamp('response_deadline')->nullable();
        $table->timestamp('resolution_deadline')->nullable();

        $table->timestamp('accepted_at')->nullable();
        $table->timestamp('responded_at')->nullable();
        $table->timestamp('resolved_at')->nullable();
        $table->timestamp('closed_at')->nullable();

        $table->text('resolution_notes')->nullable();
        $table->integer('satisfaction_score')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
