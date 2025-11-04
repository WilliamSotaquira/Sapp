<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Solo añadir columnas que no existan
            if (!Schema::hasColumn('service_requests', 'pending_at')) {
                $table->timestamp('pending_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('service_requests', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('pending_at');
            }

            if (!Schema::hasColumn('service_requests', 'in_progress_at')) {
                $table->timestamp('in_progress_at')->nullable()->after('accepted_at');
            }

            if (!Schema::hasColumn('service_requests', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('in_progress_at');
            }

            if (!Schema::hasColumn('service_requests', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('resolved_at');
            }

            if (!Schema::hasColumn('service_requests', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('closed_at');
            }

            if (!Schema::hasColumn('service_requests', 'status_notes')) {
                $table->text('status_notes')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down()
    {
        // No hacemos rollback para evitar problemas
    }
};
