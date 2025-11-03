<?php

namespace Database\Seeders;

use App\Models\Reporter;
use App\Models\Classification;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\Alert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SDMSeeder extends Seeder
{
    public function run(): void
    {
        // Clasificaciones
        $classifications = [
            [
                'name' => 'Soporte Técnico',
                'color' => '#007bff',
                'description' => 'Problemas técnicos del sistema y solicitudes de soporte',
                'order' => 1
            ],
            [
                'name' => 'Desarrollo Nuevo',
                'color' => '#28a745',
                'description' => 'Nuevas funcionalidades y desarrollos',
                'order' => 2
            ],
            [
                'name' => 'Mantenimiento',
                'color' => '#ffc107',
                'description' => 'Mantenimiento preventivo y correctivo',
                'order' => 3
            ],
            [
                'name' => 'Seguridad',
                'color' => '#dc3545',
                'description' => 'Temas de seguridad informática y acceso',
                'order' => 4
            ],
            [
                'name' => 'Infraestructura',
                'color' => '#6f42c1',
                'description' => 'Servidores, red y equipos',
                'order' => 5
            ],
        ];

        foreach ($classifications as $classification) {
            Classification::create($classification);
        }

        // Reportadores
        $reporters = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@gobierno.com',
                'department' => 'Tecnologías de la Información',
                'position' => 'Analista de Sistemas',
                'phone' => '+1234567890'
            ],
            [
                'name' => 'María García',
                'email' => 'maria.garcia@gobierno.com',
                'department' => 'Recursos Humanos',
                'position' => 'Gerente de RH',
                'phone' => '+1234567891'
            ],
            [
                'name' => 'Carlos López',
                'email' => 'carlos.lopez@gobierno.com',
                'department' => 'Finanzas',
                'position' => 'Contador General',
                'phone' => '+1234567892'
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'ana.martinez@gobierno.com',
                'department' => 'Atención Ciudadana',
                'position' => 'Coordinadora',
                'phone' => '+1234567893'
            ],
        ];

        foreach ($reporters as $reporter) {
            Reporter::create($reporter);
        }

        // Proyectos
        $projects = [
            [
                'name' => 'Portal Web Gubernamental',
                'code' => 'PORTAL-2024',
                'description' => 'Desarrollo del nuevo portal web institucional',
                'start_date' => '2024-01-15',
                'end_date' => '2024-06-30',
                'budget' => 50000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Sistema de Gestión Documental',
                'code' => 'DOCS-2024',
                'description' => 'Implementación de sistema de gestión documental electrónica',
                'start_date' => '2024-02-01',
                'end_date' => '2024-08-31',
                'budget' => 75000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Migración a la Nube',
                'code' => 'CLOUD-2024',
                'description' => 'Migración de servidores locales a infraestructura cloud',
                'start_date' => '2024-03-01',
                'status' => 'on_hold'
            ],
        ];

        foreach ($projects as $project) {
            Project::create($project);
        }

        // Alertas
        // En la sección de alertas, agrega expiration_date
        $alerts = [
            [
                'title' => 'Mantenimiento Programado',
                'message' => 'El sistema estará en mantenimiento el próximo sábado de 2:00 AM a 6:00 AM.',
                'type' => 'info',
                'alert_date' => Carbon::now()->addDays(2),
                'expiration_date' => Carbon::now()->addDays(3),
                'is_active' => true
            ],
            [
                'title' => 'Actualización de Seguridad',
                'message' => 'Se requiere actualizar los certificados de seguridad.',
                'type' => 'warning',
                'alert_date' => Carbon::now()->addDays(5),
                'expiration_date' => Carbon::now()->addDays(10),
                'is_active' => true
            ],
            [
                'title' => 'Nuevas Funcionalidades',
                'message' => 'El módulo de reportes ha sido actualizado.',
                'type' => 'success',
                'alert_date' => Carbon::now()->subDays(1),
                'expiration_date' => Carbon::now()->addDays(7),
                'is_active' => true
            ],
        ];

        foreach ($alerts as $alert) {
            Alert::create($alert);
        }

        $this->command->info('Datos de prueba creados exitosamente!');
    }
}
