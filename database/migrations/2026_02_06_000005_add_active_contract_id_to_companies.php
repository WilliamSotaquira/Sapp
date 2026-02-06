<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('active_contract_id')
                ->nullable()
                ->after('id')
                ->constrained('contracts')
                ->nullOnDelete();
        });

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE companies
                SET active_contract_id = (
                    SELECT MAX(id)
                    FROM contracts
                    WHERE contracts.company_id = companies.id
                      AND is_active = 1
                )
                WHERE active_contract_id IS NULL
            ");
        } else {
            DB::statement("
                UPDATE companies c
                JOIN (
                    SELECT company_id, MAX(id) AS contract_id
                    FROM contracts
                    WHERE is_active = 1
                    GROUP BY company_id
                ) x ON x.company_id = c.id
                SET c.active_contract_id = x.contract_id
                WHERE c.active_contract_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_contract_id');
        });
    }
};
