<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('technician_assigned_at')->nullable()->after('assigned_to');
            $table->index('technician_assigned_at', 'sr_technician_assigned_at_idx');
        });

        DB::table('service_requests')
            ->whereNotNull('assigned_to')
            ->whereNotNull('accepted_at')
            ->whereNull('technician_assigned_at')
            ->update([
                'technician_assigned_at' => DB::raw('accepted_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('sr_technician_assigned_at_idx');
            $table->dropColumn('technician_assigned_at');
        });
    }
};
