<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('company_id')->constrained('contracts');
        });

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE service_requests
                SET contract_id = (
                    SELECT sf.contract_id
                    FROM sub_services ss
                    JOIN services s ON s.id = ss.service_id
                    JOIN service_families sf ON sf.id = s.service_family_id
                    WHERE ss.id = service_requests.sub_service_id
                )
                WHERE contract_id IS NULL
            ");
        } else {
            DB::statement("
                UPDATE service_requests sr
                JOIN sub_services ss ON ss.id = sr.sub_service_id
                JOIN services s ON s.id = ss.service_id
                JOIN service_families sf ON sf.id = s.service_family_id
                SET sr.contract_id = sf.contract_id
                WHERE sr.contract_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
        });
    }
};
