<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para clonar datos de una entidad (workspace) a otra.
 * Permite seleccionar qué datos replicar: familias/servicios/SLAs, solicitantes, técnicos, departamentos.
 */
class WorkspaceCloneService
{
    /**
     * Clonar datos seleccionados de una empresa origen a una destino.
     */
    public function clone(int $sourceCompanyId, int $targetCompanyId, array $options): array
    {
        $results = [];

        if (!empty($options['structure'])) {
            $results['structure'] = $this->cloneStructure($sourceCompanyId, $targetCompanyId);
        }

        if (!empty($options['requesters'])) {
            $results['requesters'] = $this->cloneRequesters($sourceCompanyId, $targetCompanyId);
        }

        if (!empty($options['technicians'])) {
            $results['technicians'] = $this->cloneTechnicians($sourceCompanyId, $targetCompanyId);
        }

        if (!empty($options['departments'])) {
            $results['departments'] = $this->cloneDepartments($sourceCompanyId, $targetCompanyId);
        }

        return $results;
    }

    /**
     * Clonar familias, servicios, subservicios y SLAs desde el contrato activo de la empresa origen.
     */
    private function cloneStructure(int $sourceCompanyId, int $targetCompanyId): int
    {
        $sourceCompany = Company::find($sourceCompanyId);
        $targetCompany = Company::find($targetCompanyId);

        if (!$sourceCompany || !$targetCompany) return 0;

        $sourceContractId = $sourceCompany->active_contract_id;
        $targetContractId = $targetCompany->active_contract_id;

        if (!$sourceContractId || !$targetContractId) return 0;

        // Verificar que el destino no tenga familias ya
        if (DB::table('service_families')->where('contract_id', $targetContractId)->exists()) {
            return 0;
        }

        $count = 0;
        $families = DB::table('service_families')
            ->where('contract_id', $sourceContractId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        foreach ($families as $family) {
            $newFamilyId = DB::table('service_families')->insertGetId([
                'name' => $family->name,
                'code' => ($family->code ?? 'FAM-' . $family->sort_order) . '-NEW',
                'contract_id' => $targetContractId,
                'description' => $family->description,
                'sort_order' => $family->sort_order,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $services = DB::table('services')
                ->where('service_family_id', $family->id)
                ->where('is_active', 1)
                ->get();

            foreach ($services as $service) {
                $newServiceId = DB::table('services')->insertGetId([
                    'name' => $service->name,
                    'code' => ($service->code ?? 'SVC-' . $service->id) . '-NEW',
                    'service_family_id' => $newFamilyId,
                    'description' => $service->description,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $subServices = DB::table('sub_services')
                    ->where('service_id', $service->id)
                    ->where('is_active', 1)
                    ->get();

                foreach ($subServices as $ss) {
                    $newSSId = DB::table('sub_services')->insertGetId([
                        'name' => $ss->name,
                        'code' => $ss->code ? $ss->code . '-NEW' : null,
                        'service_id' => $newServiceId,
                        'description' => $ss->description,
                        'cost' => $ss->cost,
                        'is_active' => true,
                        'order' => $ss->order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $bridgeId = DB::table('service_subservices')->insertGetId([
                        'sub_service_id' => $newSSId,
                        'service_id' => $newServiceId,
                        'service_family_id' => $newFamilyId,
                        'name' => $ss->name,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Copiar SLAs del subservicio origen
                    $sourceBridge = DB::table('service_subservices')
                        ->where('sub_service_id', $ss->id)
                        ->where('service_family_id', $family->id)
                        ->first();

                    if ($sourceBridge) {
                        $sourceSLAs = DB::table('service_level_agreements')
                            ->where('service_subservice_id', $sourceBridge->id)
                            ->where('is_active', 1)
                            ->get();

                        foreach ($sourceSLAs as $sla) {
                            DB::table('service_level_agreements')->insert([
                                'name' => "SLA {$sla->criticality_level} - {$family->name} - {$ss->name}",
                                'service_subservice_id' => $bridgeId,
                                'service_family_id' => $newFamilyId,
                                'criticality_level' => $sla->criticality_level,
                                'response_time_hours' => $sla->response_time_hours,
                                'resolution_time_hours' => $sla->resolution_time_hours,
                                'acceptance_time_minutes' => $sla->acceptance_time_minutes,
                                'response_time_minutes' => $sla->response_time_minutes,
                                'resolution_time_minutes' => $sla->resolution_time_minutes,
                                'availability_percentage' => $sla->availability_percentage ?? 99,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Clonar solicitantes (requesters) de una empresa a otra.
     */
    private function cloneRequesters(int $sourceCompanyId, int $targetCompanyId): int
    {
        $requesters = DB::table('requesters')
            ->where('company_id', $sourceCompanyId)
            ->where('is_active', 1)
            ->get();

        $count = 0;
        foreach ($requesters as $requester) {
            // No duplicar si ya existe por email
            $exists = DB::table('requesters')
                ->where('company_id', $targetCompanyId)
                ->where('email', $requester->email)
                ->exists();

            if (!$exists) {
                DB::table('requesters')->insert([
                    'company_id' => $targetCompanyId,
                    'name' => $requester->name,
                    'email' => $requester->email,
                    'phone' => $requester->phone,
                    'department_id' => null, // Se asigna después si se clonan departamentos
                    'position' => $requester->position,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Vincular técnicos (users) de la empresa origen a la destino.
     */
    private function cloneTechnicians(int $sourceCompanyId, int $targetCompanyId): int
    {
        $userIds = DB::table('company_user')
            ->where('company_id', $sourceCompanyId)
            ->pluck('user_id');

        $count = 0;
        foreach ($userIds as $userId) {
            $exists = DB::table('company_user')
                ->where('company_id', $targetCompanyId)
                ->where('user_id', $userId)
                ->exists();

            if (!$exists) {
                DB::table('company_user')->insert([
                    'company_id' => $targetCompanyId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Clonar departamentos.
     */
    private function cloneDepartments(int $sourceCompanyId, int $targetCompanyId): int
    {
        $departments = DB::table('departments')
            ->where('company_id', $sourceCompanyId)
            ->where('is_active', 1)
            ->get();

        $count = 0;
        foreach ($departments as $dept) {
            $exists = DB::table('departments')
                ->where('company_id', $targetCompanyId)
                ->where('name', $dept->name)
                ->exists();

            if (!$exists) {
                DB::table('departments')->insert([
                    'company_id' => $targetCompanyId,
                    'name' => $dept->name,
                    'code' => $dept->code ? $dept->code . '-NEW' : null,
                    'description' => $dept->description,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }
}
