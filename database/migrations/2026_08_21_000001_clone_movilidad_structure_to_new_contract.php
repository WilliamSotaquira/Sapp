<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Clonar la estructura de familias, servicios, subservicios y SLAs
     * del contrato anterior de Movilidad (ID 1) al nuevo contrato 2026-2297 (ID 3).
     *
     * Las obligaciones del nuevo contrato son idénticas a las del anterior,
     * por lo que se recicla toda la estructura 1:1.
     */
    public function up(): void
    {
        $oldContractId = 1;
        $newContractId = 3;

        // Verificar que el nuevo contrato existe y no tiene familias
        $existingFamilies = DB::table('service_families')->where('contract_id', $newContractId)->count();
        if ($existingFamilies > 0) {
            echo "El contrato {$newContractId} ya tiene familias. Saltando.\n";
            return;
        }

        // Obtener familias del contrato anterior
        $oldFamilies = DB::table('service_families')
            ->where('contract_id', $oldContractId)
            ->orderBy('sort_order')
            ->get();

        foreach ($oldFamilies as $oldFamily) {
            // Crear familia nueva para el nuevo contrato
            $newFamilyId = DB::table('service_families')->insertGetId([
                'name' => $oldFamily->name,
                'code' => ($oldFamily->code ?? 'FAM-' . $oldFamily->sort_order) . '-V2',
                'contract_id' => $newContractId,
                'description' => $oldFamily->description,
                'sort_order' => $oldFamily->sort_order,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Obtener servicios de la familia anterior
            $oldServices = DB::table('services')
                ->where('service_family_id', $oldFamily->id)
                ->orderBy('name')
                ->get();

            foreach ($oldServices as $oldService) {
                // Crear servicio nuevo
                $newServiceId = DB::table('services')->insertGetId([
                    'name' => $oldService->name,
                    'code' => ($oldService->code ?? 'SVC-' . $oldService->id) . '-V2',
                    'service_family_id' => $newFamilyId,
                    'description' => $oldService->description,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Obtener subservicios
                $oldSubServices = DB::table('sub_services')
                    ->where('service_id', $oldService->id)
                    ->orderBy('name')
                    ->get();

                foreach ($oldSubServices as $oldSS) {
                    // Crear subservicio nuevo
                    $newSSId = DB::table('sub_services')->insertGetId([
                        'name' => $oldSS->name,
                        'code' => $oldSS->code ? $oldSS->code . '-V2' : null,
                        'service_id' => $newServiceId,
                        'description' => $oldSS->description,
                        'cost' => $oldSS->cost,
                        'is_active' => true,
                        'order' => $oldSS->order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Crear service_subservice bridge
                    $bridgeId = DB::table('service_subservices')->insertGetId([
                        'sub_service_id' => $newSSId,
                        'service_id' => $newServiceId,
                        'service_family_id' => $newFamilyId,
                        'name' => $oldSS->name,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Crear SLAs para cada criticidad
                    $slaLevels = [
                        'BAJA' => ['acceptance' => 120, 'response' => 240, 'resolution' => 480],
                        'MEDIA' => ['acceptance' => 60, 'response' => 120, 'resolution' => 360],
                        'ALTA' => ['acceptance' => 30, 'response' => 60, 'resolution' => 240],
                        'CRITICA' => ['acceptance' => 15, 'response' => 30, 'resolution' => 120],
                    ];

                    foreach ($slaLevels as $criticality => $times) {
                        DB::table('service_level_agreements')->insert([
                            'name' => "SLA {$criticality} - {$oldFamily->name} - {$oldSS->name}",
                            'description' => null,
                            'service_subservice_id' => $bridgeId,
                            'service_family_id' => $newFamilyId,
                            'criticality_level' => $criticality,
                            'response_time_hours' => round($times['response'] / 60, 2),
                            'resolution_time_hours' => round($times['resolution'] / 60, 2),
                            'acceptance_time_minutes' => $times['acceptance'],
                            'response_time_minutes' => $times['response'],
                            'resolution_time_minutes' => $times['resolution'],
                            'availability_percentage' => 99.00,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse: eliminar todo lo creado para el contrato nuevo.
     */
    public function down(): void
    {
        $newContractId = 3;

        $familyIds = DB::table('service_families')->where('contract_id', $newContractId)->pluck('id');

        if ($familyIds->isEmpty()) return;

        // Eliminar SLAs
        DB::table('service_level_agreements')->whereIn('service_family_id', $familyIds)->delete();

        // Eliminar service_subservices bridges
        DB::table('service_subservices')->whereIn('service_family_id', $familyIds)->delete();

        // Obtener service IDs
        $serviceIds = DB::table('services')->whereIn('service_family_id', $familyIds)->pluck('id');

        // Eliminar sub_services
        DB::table('sub_services')->whereIn('service_id', $serviceIds)->delete();

        // Eliminar services
        DB::table('services')->whereIn('id', $serviceIds)->delete();

        // Eliminar familias
        DB::table('service_families')->whereIn('id', $familyIds)->delete();
    }
};
