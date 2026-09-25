<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Autoría dual (modelo single-user con delegación).
 *
 * El sistema lo opera un único líder. Cuando delega una solicitud a un técnico,
 * el trabajo lo EJECUTA el técnico (service_requests.assigned_to) pero la
 * RESPONSABILIDAD de control/verificación ante el contrato es del líder.
 *
 * Esta migración añade el rastro de esa verificación:
 *  - service_requests.verified_by / verified_at: quién (el líder) verificó el
 *    contenido publicado por el técnico y cuándo. El ejecutor ya vive en
 *    assigned_to; esto captura el otro lado de la autoría dual.
 *  - tasks.owner_id: responsable/supervisor de la tarea, separado de
 *    technician_id (ejecutor). Base para la tarea de control del líder (Fase 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'verified_by')) {
                $table->foreignId('verified_by')
                    ->nullable()
                    ->after('closed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('service_requests', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'owner_id')) {
                $table->foreignId('owner_id')
                    ->nullable()
                    ->after('technician_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'verified_by')) {
                $table->dropConstrainedForeignId('verified_by');
            }
            if (Schema::hasColumn('service_requests', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });
    }
};
