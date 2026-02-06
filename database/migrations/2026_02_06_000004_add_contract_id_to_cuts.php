<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuts', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('id')->constrained('contracts');
        });

        // Backfill: asignar contrato según solicitudes asociadas (si existen)
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE cuts
                SET contract_id = (
                    SELECT MAX(sr.contract_id)
                    FROM cut_service_request csr
                    JOIN service_requests sr ON sr.id = csr.service_request_id
                    WHERE csr.cut_id = cuts.id
                      AND sr.contract_id IS NOT NULL
                )
                WHERE contract_id IS NULL
            ");
        } else {
            DB::statement("
                UPDATE cuts c
                JOIN (
                    SELECT csr.cut_id, MAX(sr.contract_id) AS contract_id
                    FROM cut_service_request csr
                    JOIN service_requests sr ON sr.id = csr.service_request_id
                    WHERE sr.contract_id IS NOT NULL
                    GROUP BY csr.cut_id
                ) x ON x.cut_id = c.id
                SET c.contract_id = x.contract_id
                WHERE c.contract_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('cuts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
        });
    }
};
