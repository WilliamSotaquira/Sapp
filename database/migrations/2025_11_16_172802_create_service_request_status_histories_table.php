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
        Schema::create('service_request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->string('status', 50); // NUEVA, EN_REVISION, ACEPTADA, EN_PROGRESO, etc.
            $table->string('previous_status', 50)->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Para datos adicionales
            $table->timestamps();

            // Índices para mejorar rendimiento con nombres cortos
            $table->index(['service_request_id', 'created_at'], 'sr_status_hist_req_created_idx');
            $table->index('status', 'sr_status_hist_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_status_histories');
    }
};
