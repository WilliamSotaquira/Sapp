<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cut_service_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cut_id')->constrained('cuts')->cascadeOnDelete();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cut_id', 'service_request_id']);
            $table->index(['service_request_id', 'cut_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cut_service_request');
    }
};
