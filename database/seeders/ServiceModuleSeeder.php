<?php

namespace Database\Seeders;

use App\Models\ServiceFamily;
use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceLevelAgreement;
use Illuminate\Database\Seeder;

class ServiceModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Usar firstOrCreate para evitar duplicados
        $soporteFamily = ServiceFamily::firstOrCreate(
            ['code' => 'ST'],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Servicios de soporte técnico general',
                'is_active' => true
            ]
        );

        $infraFamily = ServiceFamily::firstOrCreate(
            ['code' => 'ITI'],
            [
                'name' => 'Infraestructura TI',
                'description' => 'Servicios de infraestructura tecnológica',
                'is_active' => true
            ]
        );

        $desarrolloFamily = ServiceFamily::firstOrCreate(
            ['code' => 'DEV'],
            [
                'name' => 'Desarrollo',
                'description' => 'Servicios de desarrollo de software',
                'is_active' => true
            ]
        );

        // Crear Servicios para Soporte Técnico
        $soporteSoftware = Service::firstOrCreate(
            ['service_family_id' => $soporteFamily->id, 'code' => 'SSW'],
            [
                'name' => 'Soporte de Software',
                'description' => 'Soporte para aplicaciones de software',
                'is_active' => true,
                'order' => 1
            ]
        );

        $soporteHardware = Service::firstOrCreate(
            ['service_family_id' => $soporteFamily->id, 'code' => 'SHW'],
            [
                'name' => 'Soporte de Hardware',
                'description' => 'Soporte para equipos y dispositivos',
                'is_active' => true,
                'order' => 2
            ]
        );

        // Crear Servicios para Infraestructura
        $redesService = Service::firstOrCreate(
            ['service_family_id' => $infraFamily->id, 'code' => 'RED'],
            [
                'name' => 'Servicios de Red',
                'description' => 'Gestión y mantenimiento de red',
                'is_active' => true,
                'order' => 1
            ]
        );

        // Crear Sub-Servicios
        $officeSubService = SubService::firstOrCreate(
            ['service_id' => $soporteSoftware->id, 'code' => 'OFFICE'],
            [
                'name' => 'Instalación de Office',
                'description' => 'Instalación y configuración de Microsoft Office',
                'is_active' => true,
                'cost' => 0,
                'order' => 1
            ]
        );

        $antivirusSubService = SubService::firstOrCreate(
            ['service_id' => $soporteSoftware->id, 'code' => 'ANTIV'],
            [
                'name' => 'Instalación de Antivirus',
                'description' => 'Instalación y configuración de software antivirus',
                'is_active' => true,
                'cost' => 0,
                'order' => 2
            ]
        );

        $mantenimientoSubService = SubService::firstOrCreate(
            ['service_id' => $soporteHardware->id, 'code' => 'MANT'],
            [
                'name' => 'Mantenimiento Preventivo',
                'description' => 'Mantenimiento preventivo de equipos',
                'is_active' => true,
                'cost' => 50.00,
                'order' => 1
            ]
        );

        // Crear SLAs
        $slaBasico = ServiceLevelAgreement::firstOrCreate(
            ['service_family_id' => $soporteFamily->id, 'name' => 'SLA Básico Soporte'],
            [
                'criticality_level' => 'MEDIA',
                'acceptance_time_minutes' => 30,
                'response_time_minutes' => 120,
                'resolution_time_minutes' => 480,
                'conditions' => 'Aplicable para solicitudes estándar',
                'is_active' => true
            ]
        );

        $slaUrgente = ServiceLevelAgreement::firstOrCreate(
            ['service_family_id' => $soporteFamily->id, 'name' => 'SLA Urgente'],
            [
                'criticality_level' => 'ALTA',
                'acceptance_time_minutes' => 15,
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 240,
                'conditions' => 'Para situaciones críticas que afectan operaciones',
                'is_active' => true
            ]
        );

        $slaInfra = ServiceLevelAgreement::firstOrCreate(
            ['service_family_id' => $infraFamily->id, 'name' => 'SLA Infraestructura'],
            [
                'criticality_level' => 'ALTA',
                'acceptance_time_minutes' => 20,
                'response_time_minutes' => 90,
                'resolution_time_minutes' => 360,
                'conditions' => 'Para servicios de infraestructura crítica',
                'is_active' => true
            ]
        );

        $this->command->info('Datos del módulo de servicios verificados/creados exitosamente!');
        $this->command->info('- Familias de servicio: 3');
        $this->command->info('- Servicios: 3');
        $this->command->info('- Sub-servicios: 3');
        $this->command->info('- SLAs: 3');
    }
}
