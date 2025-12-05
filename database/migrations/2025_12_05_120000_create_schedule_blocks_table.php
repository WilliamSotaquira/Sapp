<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea la tabla para bloqueos de horario
     * (reuniones, almuerzos, tiempo no disponible, etc.)
     */
    public function up(): void
    {
        // Verificar si la tabla ya existe
        if (Schema::hasTable('schedule_blocks')) {
            // Agregar columnas faltantes si no existen
            Schema::table('schedule_blocks', function (Blueprint $table) {
                if (!Schema::hasColumn('schedule_blocks', 'block_type')) {
                    $table->enum('block_type', ['meeting', 'lunch', 'break', 'unavailable', 'vacation', 'training', 'other'])->default('unavailable')->after('technician_id');
                }
                if (!Schema::hasColumn('schedule_blocks', 'color')) {
                    $table->string('color', 7)->default('#6B7280')->after('end_time');
                }
                if (!Schema::hasColumn('schedule_blocks', 'is_recurring')) {
                    $table->boolean('is_recurring')->default(false)->after('color');
                }
                if (!Schema::hasColumn('schedule_blocks', 'recurrence_pattern')) {
                    $table->json('recurrence_pattern')->nullable()->after('is_recurring');
                }
            });
            return;
        }

        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->onDelete('cascade');
            $table->enum('block_type', ['meeting', 'lunch', 'break', 'unavailable', 'vacation', 'training', 'other'])->default('unavailable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('block_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('color', 7)->default('#6B7280'); // Color hex
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_pattern')->nullable(); // Para patrones de recurrencia
            $table->timestamps();

            // Índices
            $table->index(['technician_id', 'block_date']);
            $table->index('block_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
    }
};
