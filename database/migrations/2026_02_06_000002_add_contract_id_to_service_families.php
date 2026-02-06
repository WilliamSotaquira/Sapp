<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_families', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('id')->constrained('contracts');
        });

        $company = DB::table('companies')->where('name', 'Movilidad')->first();
        if (!$company) {
            return;
        }

        $contract = DB::table('contracts')
            ->where('company_id', $company->id)
            ->where('number', '20251069')
            ->first();

        if (!$contract) {
            $contractId = DB::table('contracts')->insertGetId([
                'company_id' => $company->id,
                'number' => '20251069',
                'name' => 'Contrato 20251069',
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $contractId = $contract->id;
        }

        DB::table('service_families')
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);
    }

    public function down(): void
    {
        Schema::table('service_families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
        });
    }
};
