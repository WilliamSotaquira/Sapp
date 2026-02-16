<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceSubservice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CreateSlasForContract extends Command
{
    protected $signature = 'sla:create-for-contract
                            {contractNumber : Numero de contrato (ej: 0813-2026)}
                            {--dry-run : Solo mostrar lo que se crearia, sin escribir en BD}';

    protected $description = 'Crear SLAs por criticidad para todos los subservicios de un contrato';

    public function handle(): int
    {
        $contractNumber = trim((string) $this->argument('contractNumber'));

        $contract = Contract::query()
            ->where('number', $contractNumber)
            ->first();

        if (!$contract) {
            $this->error("No se encontro el contrato con numero: {$contractNumber}");
            return self::FAILURE;
        }

        $serviceSubservices = ServiceSubservice::query()
            ->where('is_active', true)
            ->whereHas('serviceFamily', function ($query) use ($contract) {
                $query->where('contract_id', $contract->id);
            })
            ->with(['serviceFamily:id,contract_id,name', 'service:id,name', 'subService:id,name'])
            ->get();

        if ($serviceSubservices->isEmpty()) {
            $this->warn("No hay subservicios activos asociados al contrato {$contractNumber}.");
            return self::SUCCESS;
        }

        $slaTemplates = [
            'BAJA' => [
                'acceptance_time_minutes' => 120,
                'response_time_minutes' => 240,
                'resolution_time_minutes' => 480,
            ],
            'MEDIA' => [
                'acceptance_time_minutes' => 60,
                'response_time_minutes' => 120,
                'resolution_time_minutes' => 360,
            ],
            'ALTA' => [
                'acceptance_time_minutes' => 30,
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 240,
            ],
            'CRITICA' => [
                'acceptance_time_minutes' => 15,
                'response_time_minutes' => 30,
                'resolution_time_minutes' => 120,
            ],
        ];

        $hasServiceSubserviceId = Schema::hasColumn('service_level_agreements', 'service_subservice_id');
        $hasSubServiceId = Schema::hasColumn('service_level_agreements', 'sub_service_id');
        $hasServiceId = Schema::hasColumn('service_level_agreements', 'service_id');
        $hasResponseTimeHours = Schema::hasColumn('service_level_agreements', 'response_time_hours');
        $hasResolutionTimeHours = Schema::hasColumn('service_level_agreements', 'resolution_time_hours');
        $hasAvailabilityPercentage = Schema::hasColumn('service_level_agreements', 'availability_percentage');
        $hasDescription = Schema::hasColumn('service_level_agreements', 'description');

        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $skipped = 0;

        foreach ($serviceSubservices as $subservice) {
            foreach ($slaTemplates as $criticality => $times) {
                $existsQuery = ServiceLevelAgreement::query()
                    ->withTrashed()
                    ->where('criticality_level', $criticality)
                    ->where('service_family_id', $subservice->service_family_id);

                if ($hasServiceSubserviceId) {
                    $existsQuery->where('service_subservice_id', $subservice->id);
                } else {
                    $existsQuery->forSubService($subservice->sub_service_id);
                }

                if ($existsQuery->exists()) {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'service_family_id' => $subservice->service_family_id,
                    'name' => "SLA {$criticality} - {$subservice->name}",
                    'criticality_level' => $criticality,
                    'acceptance_time_minutes' => $times['acceptance_time_minutes'],
                    'response_time_minutes' => $times['response_time_minutes'],
                    'resolution_time_minutes' => $times['resolution_time_minutes'],
                    'conditions' => "SLA estandar para {$subservice->name} con criticidad {$criticality}",
                    'is_active' => true,
                ];

                if ($hasServiceSubserviceId) {
                    $payload['service_subservice_id'] = $subservice->id;
                }

                if ($hasSubServiceId) {
                    $payload['sub_service_id'] = $subservice->sub_service_id;
                }

                if ($hasServiceId) {
                    $payload['service_id'] = $subservice->service_id;
                }

                if ($hasResponseTimeHours) {
                    $payload['response_time_hours'] = (int) ceil($times['response_time_minutes'] / 60);
                }

                if ($hasResolutionTimeHours) {
                    $payload['resolution_time_hours'] = (int) ceil($times['resolution_time_minutes'] / 60);
                }

                if ($hasAvailabilityPercentage) {
                    $payload['availability_percentage'] = 99.9;
                }

                if ($hasDescription) {
                    $payload['description'] = $payload['conditions'];
                }

                if ($dryRun) {
                    $created++;
                    continue;
                }

                $sla = ServiceLevelAgreement::query()->create($payload);

                if ($hasSubServiceId && !in_array('sub_service_id', $sla->getFillable(), true)) {
                    $sla->forceFill(['sub_service_id' => $subservice->sub_service_id])->save();
                }

                $created++;
            }
        }

        if ($dryRun) {
            $this->info("Dry run: se crearian {$created} SLAs. Se omitieron {$skipped} por duplicados.");
            return self::SUCCESS;
        }

        $this->info("Listo. Se crearon {$created} SLAs. Se omitieron {$skipped} por duplicados.");
        return self::SUCCESS;
    }
}
