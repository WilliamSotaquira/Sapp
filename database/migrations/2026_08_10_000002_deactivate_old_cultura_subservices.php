<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Desactiva los subservicios, servicios y pivotes de las familias antiguas de Cultura
 * para que el clasificador solo encuentre los nuevos (con prefijo CUL_F*).
 */
return new class extends Migration
{
    public function up(): void
    {
        $contract = DB::table('contracts')->where('number', '0813-2026')->first();
        if (!$contract) {
            return;
        }

        // Obtener IDs de familias INACTIVAS de este contrato (las antiguas)
        $inactiveFamilyIds = DB::table('service_families')
            ->where('contract_id', $contract->id)
            ->where('is_active', false)
            ->pluck('id')
            ->toArray();

        if (empty($inactiveFamilyIds)) {
            return;
        }

        // Obtener servicios de esas familias
        $serviceIds = DB::table('services')
            ->whereIn('service_family_id', $inactiveFamilyIds)
            ->pluck('id')
            ->toArray();

        // Desactivar servicios
        DB::table('services')
            ->whereIn('id', $serviceIds)
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Desactivar subservicios de esos servicios
        DB::table('sub_services')
            ->whereIn('service_id', $serviceIds)
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Desactivar pivotes de esas familias
        DB::table('service_subservices')
            ->whereIn('service_family_id', $inactiveFamilyIds)
            ->update(['is_active' => false, 'updated_at' => now()]);

        Log::info('Migración: Desactivados subservicios/servicios/pivotes de familias antiguas de Cultura.', [
            'families_count' => count($inactiveFamilyIds),
            'services_count' => count($serviceIds),
        ]);
    }

    public function down(): void
    {
        Log::info('Rollback: Use el backup JSON para restaurar.');
    }
};
