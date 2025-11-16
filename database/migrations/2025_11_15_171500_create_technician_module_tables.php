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
        // Tabla: technicians (Técnicos)
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('specialties')->nullable()->comment('Skills: Frontend, Backend, DevOps, etc.');
            $table->enum('experience_level', ['junior', 'mid', 'senior', 'lead'])->default('mid');
            $table->boolean('remote_available')->default(true);
            $table->time('work_start_time')->default('08:00:00');
            $table->time('work_end_time')->default('17:00:00');
            $table->enum('status', ['active', 'inactive', 'on_vacation', 'in_task'])->default('active');
            $table->integer('daily_capacity_minutes')->default(480)->comment('8 horas = 480 minutos');
            $table->integer('max_concurrent_tasks')->default(1)->comment('Tareas asíncronas permitidas');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'user_id']);
        });

        // Tabla: tasks (Tareas de Soporte/Desarrollo)
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code', 50)->unique()->comment('Formato: IMP-20251115-001 o REG-20251115-004');
            $table->enum('type', ['impact', 'regular'])->comment('IMPACT: 90min, REGULAR: 25min');
            $table->string('title');
            $table->text('description')->nullable();

            // Relaciones
            $table->foreignId('service_request_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('technician_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sla_id')->nullable()->constrained('service_level_agreements')->onDelete('set null');

            // Temporalidad
            $table->dateTime('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();

            // Estado y prioridad
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'blocked', 'in_review', 'completed', 'cancelled', 'rescheduled'])->default('pending');

            // Complejidad y contexto técnico
            $table->tinyInteger('technical_complexity')->default(3)->comment('1-5');
            $table->json('required_accesses')->nullable()->comment('Servidores, BD, Repositorios');
            $table->json('dependencies')->nullable()->comment('Tareas o recursos externos');
            $table->json('technologies')->nullable()->comment('Tags: Laravel, React, etc.');
            $table->string('git_repository')->nullable();
            $table->string('git_branch')->nullable();
            $table->string('git_pr_url')->nullable();
            $table->enum('environment', ['development', 'staging', 'production'])->nullable();

            // Notas y documentación
            $table->text('technical_notes')->nullable();
            $table->integer('research_time_minutes')->nullable();

            // Timestamps de seguimiento
            $table->timestamp('started_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->text('block_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['scheduled_date', 'technician_id']);
            $table->index(['type', 'status']);
            $table->index(['technician_id', 'status']);
            $table->index('task_code');
        });

        // Tabla: schedule_blocks (Bloques de Horario)
        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->onDelete('cascade');
            $table->date('block_date');
            $table->enum('block_type', ['morning_impact', 'afternoon_regular', 'meeting', 'learning', 'planning'])->default('afternoon_regular');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['available', 'occupied', 'blocked', 'meeting'])->default('available');
            $table->enum('work_type', ['focused', 'collaborative', 'async'])->nullable();
            $table->text('block_reason')->nullable()->comment('Razón de bloqueo si aplica');
            $table->timestamps();

            $table->index(['technician_id', 'block_date']);
            $table->index(['block_date', 'status']);
        });

        // Tabla: task_histories (Historial de Tareas)
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

        // Tabla: capacity_rules (Reglas de Capacidad)
        Schema::create('capacity_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->nullable()->constrained()->onDelete('cascade')->comment('NULL = regla global');
            $table->enum('day_type', ['weekday', 'weekend', 'holiday'])->default('weekday');
            $table->tinyInteger('max_impact_tasks_morning')->default(2);
            $table->tinyInteger('max_regular_tasks_afternoon')->default(6);
            $table->integer('impact_task_duration_minutes')->default(90);
            $table->integer('regular_task_duration_minutes')->default(25);
            $table->integer('buffer_between_tasks_minutes')->default(5);
            $table->integer('documentation_time_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['technician_id', 'is_active']);
        });

        // Tabla: sla_compliance (Cumplimiento de SLA)
        Schema::create('sla_compliance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_request_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sla_id')->constrained('service_level_agreements')->onDelete('cascade');

            // Tiempos SLA
            $table->integer('sla_response_time_minutes')->nullable();
            $table->integer('sla_resolution_time_minutes')->nullable();

            // Tiempos reales
            $table->integer('actual_response_time_minutes')->nullable();
            $table->integer('actual_resolution_time_minutes')->nullable();

            // Estado de cumplimiento
            $table->enum('compliance_status', ['within_sla', 'at_risk', 'breached'])->default('within_sla');
            $table->decimal('compliance_percentage', 5, 2)->nullable();
            $table->dateTime('sla_deadline')->nullable();
            $table->text('breach_reason')->nullable();
            $table->timestamps();

            $table->index(['compliance_status', 'sla_deadline']);
            $table->index('task_id');
        });

        // Tabla: task_dependencies (Dependencias de Tareas)
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade')->comment('Tarea principal');
            $table->foreignId('depends_on_task_id')->constrained('tasks')->onDelete('cascade')->comment('Tarea de la que depende');
            $table->enum('dependency_type', ['blocks', 'blocked_by', 'related'])->default('blocks');
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->timestamps();

            $table->index(['task_id', 'status']);
            $table->unique(['task_id', 'depends_on_task_id']);
        });

        // Tabla: technician_skills (Skills de Técnicos)
        Schema::create('technician_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->onDelete('cascade');
            $table->string('skill_name')->comment('Laravel, React, DevOps, etc.');
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->integer('years_experience')->default(0);
            $table->boolean('is_primary')->default(false)->comment('Skill principal del técnico');
            $table->timestamps();

            $table->index(['technician_id', 'skill_name']);
        });

        // Tabla: task_git_associations (Asociaciones con Git)
        Schema::create('task_git_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->string('repository_url');
            $table->string('branch_name')->nullable();
            $table->string('commit_hash')->nullable();
            $table->string('pull_request_url')->nullable();
            $table->enum('pr_status', ['open', 'merged', 'closed'])->nullable();
            $table->text('commit_message')->nullable();
            $table->timestamps();

            $table->index('task_id');
        });

        // Tabla: knowledge_base_links (Base de Conocimiento)
        Schema::create('knowledge_base_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('url')->nullable();
            $table->text('content')->nullable();
            $table->enum('type', ['documentation', 'solution', 'snippet', 'lesson_learned'])->default('documentation');
            $table->boolean('is_reusable')->default(false);
            $table->timestamps();

            $table->index(['task_id', 'type']);
        });

        // Tabla: environment_access (Acceso a Ambientes)
        Schema::create('environment_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->onDelete('cascade');
            $table->string('environment_name')->comment('Producción, Staging, Dev Server 1, etc.');
            $table->enum('environment_type', ['development', 'staging', 'production', 'testing']);
            $table->enum('access_level', ['read', 'write', 'admin'])->default('read');
            $table->boolean('has_access')->default(true);
            $table->date('access_granted_at')->nullable();
            $table->date('access_expires_at')->nullable();
            $table->timestamps();

            $table->index(['technician_id', 'has_access']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_access');
        Schema::dropIfExists('knowledge_base_links');
        Schema::dropIfExists('task_git_associations');
        Schema::dropIfExists('technician_skills');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('sla_compliance');
        Schema::dropIfExists('capacity_rules');
        Schema::dropIfExists('task_history');
        Schema::dropIfExists('schedule_blocks');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('technicians');
    }
};
