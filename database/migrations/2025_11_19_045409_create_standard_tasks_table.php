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
        Schema::create('standard_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_service_id')->constrained('sub_services')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['impact', 'regular'])->default('regular');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->decimal('estimated_hours', 5, 2)->default(1.0);
            $table->integer('technical_complexity')->nullable(); // 1-5
            $table->string('technologies')->nullable();
            $table->string('required_accesses')->nullable();
            $table->enum('environment', ['development', 'staging', 'production'])->nullable();
            $table->text('technical_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_tasks');
    }
};
