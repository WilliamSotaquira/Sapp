<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_requests')) {
            return;
        }

        if (!Schema::hasColumn('service_requests', 'entry_channel')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->string('entry_channel', 30)
                    ->nullable()
                    ->after('requested_by');
            });
        }

        DB::table('service_requests')
            ->where('entry_channel', 'correo_corporativo')
            ->update(['entry_channel' => 'email_corporativo']);

        DB::table('service_requests')
            ->where('entry_channel', 'llamada')
            ->update(['entry_channel' => 'telefono']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('service_requests')) {
            return;
        }

        DB::table('service_requests')
            ->where('entry_channel', 'email_corporativo')
            ->update(['entry_channel' => 'correo_corporativo']);

        DB::table('service_requests')
            ->where('entry_channel', 'telefono')
            ->update(['entry_channel' => 'llamada']);
    }
};
