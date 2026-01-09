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
        if (Schema::hasTable('task_histories')) {
            return;
        }

        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['created', 'assigned', 'started', 'paused', 'resumed', 'completed', 'cancelled', 'rescheduled', 'blocked', 'unblocked']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Usuario que ejecuta la acción');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Datos adicionales del cambio');
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_histories');
    }
};
