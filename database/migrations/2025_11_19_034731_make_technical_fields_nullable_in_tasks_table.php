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
            // Hacer nullable los campos técnicos opcionales
            $table->integer('technical_complexity')->nullable()->change();
            $table->text('technical_notes')->nullable()->change();
            $table->enum('environment', ['development', 'staging', 'production'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Revertir los cambios
            $table->integer('technical_complexity')->nullable(false)->change();
            $table->text('technical_notes')->nullable(false)->change();
            $table->enum('environment', ['development', 'staging', 'production'])->nullable(false)->change();
        });
    }
};
