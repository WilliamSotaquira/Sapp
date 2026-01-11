<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceSubservice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SLASeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('service_level_agreements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $hasServiceSubserviceId = Schema::hasColumn('service_level_agreements', 'service_subservice_id');
        $hasSubServiceId = Schema::hasColumn('service_level_agreements', 'sub_service_id');
        $hasResponseTimeHours = Schema::hasColumn('service_level_agreements', 'response_time_hours');
        $hasResolutionTimeHours = Schema::hasColumn('service_level_agreements', 'resolution_time_hours');
        $hasAvailabilityPercentage = Schema::hasColumn('service_level_agreements', 'availability_percentage');

        $subservices = ServiceSubservice::query()->where('is_active', true)->get();

        $slaTemplates = [
            'BAJA' => [
                'acceptance_time_minutes' => 120,
                'response_time_minutes' => 240,
                'resolution_time_minutes' => 480
            ],
            'MEDIA' => [
                'acceptance_time_minutes' => 60,
                'response_time_minutes' => 120,
                'resolution_time_minutes' => 360
            ],
            'ALTA' => [
                'acceptance_time_minutes' => 30,
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 240
            ],
            'CRITICA' => [
                'acceptance_time_minutes' => 15,
                'response_time_minutes' => 30,
                'resolution_time_minutes' => 120
            ]
        ];

        foreach ($subservices as $subservice) {
            if (empty($subservice->service_family_id)) {
                $this->command->warn("ServiceSubservice sin service_family_id: {$subservice->id}");
                continue;
            }

            if ($hasSubServiceId && empty($subservice->sub_service_id)) {
                $this->command->warn("ServiceSubservice sin sub_service_id: {$subservice->id}");
                continue;
            }

            // Crear un SLA para cada nivel de criticidad
            foreach ($slaTemplates as $criticality => $times) {
                $payload = [
                    'service_family_id' => $subservice->service_family_id,
                    'name' => "SLA {$criticality} - {$subservice->name}",
                    'criticality_level' => $criticality,
                    'acceptance_time_minutes' => $times['acceptance_time_minutes'],
                    'response_time_minutes' => $times['response_time_minutes'],
                    'resolution_time_minutes' => $times['resolution_time_minutes'],
                    'conditions' => "SLA estándar para {$subservice->name} con criticidad {$criticality}",
                    'is_active' => true,
                ];

                if ($hasServiceSubserviceId) {
                    $payload['service_subservice_id'] = $subservice->id;
                }

                if ($hasSubServiceId) {
                    $payload['sub_service_id'] = $subservice->sub_service_id;
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

                ServiceLevelAgreement::query()->create($payload);
            }
        }

        $this->command->info('SLAs de prueba creados exitosamente!');
    }
}
