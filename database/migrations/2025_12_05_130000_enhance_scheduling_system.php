<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración para mejorar el sistema de agendamiento
     * - Añadir campos para evidencia obligatoria
     * - Añadir fecha de vencimiento a tareas
     * - Añadir tiempo estimado a subtasks
     * - Añadir campos para bloques de 25 min
     */
    public function up(): void
    {
        // Agregar campos a tasks
        Schema::table('tasks', function (Blueprint $table) {
            // Fecha de vencimiento para tareas críticas
            if (!Schema::hasColumn('tasks', 'due_date')) {
                $table->date('due_date')->nullable()->after('scheduled_date');
            }
            if (!Schema::hasColumn('tasks', 'due_time')) {
                $table->time('due_time')->nullable()->after('due_date');
            }

            // Flag para indicar si requiere evidencia (por defecto sí)
            if (!Schema::hasColumn('tasks', 'requires_evidence')) {
                $table->boolean('requires_evidence')->default(true)->after('technical_notes');
            }

            // Flag para indicar si tiene evidencia completa
            if (!Schema::hasColumn('tasks', 'evidence_completed')) {
                $table->boolean('evidence_completed')->default(false)->after('requires_evidence');
            }

            // Número de bloques de 25 minutos que requiere
            if (!Schema::hasColumn('tasks', 'time_blocks')) {
                $table->integer('time_blocks')->default(1)->after('estimated_hours');
            }

            // Indicar si es tarea crítica (basado en prioridad y fecha vencimiento)
            if (!Schema::hasColumn('tasks', 'is_critical')) {
                $table->boolean('is_critical')->default(false)->after('priority');
            }

            // Standard task ID para tareas repetitivas/preestablecidas
            if (!Schema::hasColumn('tasks', 'standard_task_id')) {
                $table->foreignId('standard_task_id')->nullable()->after('project_id');
            }
        });

        // Agregar campos a subtasks
        Schema::table('subtasks', function (Blueprint $table) {
            // Tiempo estimado en minutos (por defecto 25 min)
            if (!Schema::hasColumn('subtasks', 'estimated_minutes')) {
                $table->integer('estimated_minutes')->default(25)->after('priority');
            }

            // Flag para indicar si requiere evidencia
            if (!Schema::hasColumn('subtasks', 'requires_evidence')) {
                $table->boolean('requires_evidence')->default(true)->after('estimated_minutes');
            }

            // Flag para indicar si tiene evidencia completa
            if (!Schema::hasColumn('subtasks', 'evidence_completed')) {
                $table->boolean('evidence_completed')->default(false)->after('requires_evidence');
            }

            // Tiempo real tomado
            if (!Schema::hasColumn('subtasks', 'actual_minutes')) {
                $table->integer('actual_minutes')->nullable()->after('estimated_minutes');
            }

            // Hora de inicio
            if (!Schema::hasColumn('subtasks', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('completed_at');
            }
        });

        // Crear tabla para alertas de tareas críticas
        if (!Schema::hasTable('task_alerts')) {
            Schema::create('task_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('alert_type', ['due_soon', 'overdue', 'blocked', 'no_evidence', 'critical_pending']);
                $table->string('message');
                $table->boolean('is_read')->default(false);
                $table->boolean('is_dismissed')->default(false);
                $table->timestamp('alert_at');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_read']);
                $table->index(['task_id', 'alert_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $columns = ['due_date', 'due_time', 'requires_evidence', 'evidence_completed', 'time_blocks', 'is_critical', 'standard_task_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('subtasks', function (Blueprint $table) {
            $columns = ['estimated_minutes', 'requires_evidence', 'evidence_completed', 'actual_minutes', 'started_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('subtasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('task_alerts');
    }
};
