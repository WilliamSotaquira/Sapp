<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceSubservice;

class SLASeeder extends Seeder
{
    public function run()
    {
        $subservices = ServiceSubservice::all();

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
            // Crear un SLA para cada nivel de criticidad
            foreach ($slaTemplates as $criticality => $times) {
                ServiceLevelAgreement::create([
                    'service_subservice_id' => $subservice->id,
                    'name' => "SLA {$criticality} - {$subservice->name}",
                    'criticality_level' => $criticality,
                    'acceptance_time_minutes' => $times['acceptance_time_minutes'],
                    'response_time_minutes' => $times['response_time_minutes'],
                    'resolution_time_minutes' => $times['resolution_time_minutes'],
                    'conditions' => "SLA estándar para {$subservice->name} con criticidad {$criticality}",
                    'is_active' => true
                ]);
            }
        }

        $this->command->info('SLAs de prueba creados exitosamente!');
    }
}
