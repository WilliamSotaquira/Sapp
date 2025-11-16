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
        Schema::table('technicians', function (Blueprint $table) {
            // Agregar nuevas columnas
            $table->string('specialization', 50)->after('user_id')->nullable();
            $table->decimal('years_experience', 4, 1)->after('specialization')->default(0);
            $table->enum('skill_level', ['junior', 'mid', 'senior', 'lead'])->after('years_experience')->default('mid');
            $table->decimal('max_daily_capacity_hours', 3, 1)->after('skill_level')->default(8.0);
            $table->enum('availability_status', ['available', 'busy', 'on_leave', 'unavailable'])->after('status')->default('available');

            // Eliminar columnas antiguas que no se usan
            $table->dropColumn([
                'specialties',
                'experience_level',
                'remote_available',
                'work_start_time',
                'work_end_time',
                'daily_capacity_minutes',
                'max_concurrent_tasks'
            ]);

            // Actualizar enum de status
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            // Restaurar columnas antiguas
            $table->json('specialties')->nullable();
            $table->enum('experience_level', ['junior', 'mid', 'senior', 'lead'])->default('mid');
            $table->boolean('remote_available')->default(true);
            $table->time('work_start_time')->default('08:00:00');
            $table->time('work_end_time')->default('17:00:00');
            $table->integer('daily_capacity_minutes')->default(480);
            $table->integer('max_concurrent_tasks')->default(1);

            // Eliminar nuevas columnas
            $table->dropColumn([
                'specialization',
                'years_experience',
                'skill_level',
                'max_daily_capacity_hours',
                'availability_status'
            ]);
        });
    }
};
