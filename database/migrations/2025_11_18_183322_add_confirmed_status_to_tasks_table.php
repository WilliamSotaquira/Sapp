<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiar el tipo de la columna status para incluir 'confirmed'
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'confirmed', 'in_progress', 'blocked', 'in_review', 'completed', 'cancelled', 'rescheduled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original sin 'confirmed'
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'blocked', 'in_review', 'completed', 'cancelled', 'rescheduled') DEFAULT 'pending'");
    }
};
