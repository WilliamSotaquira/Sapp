<?php
// database/seeders/ServiceSeeder.php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [
            // ==================== TECNOLOGÍA INFORMACIÓN (ITS) ====================
            [
                'service_family_id' => 1, // ITS
                'name' => 'Soporte de Aplicaciones',
                'code' => 'AP',
                'description' => 'Soporte técnico para aplicaciones empresariales y software',
                'is_active' => true,
                'order' => 1
            ],
            [
                'service_family_id' => 1, // ITS
                'name' => 'Gestión de Base de Datos',
                'code' => 'DB',
                'description' => 'Administración, mantenimiento y optimización de bases de datos',
                'is_active' => true,
                'order' => 2
            ],
            [
                'service_family_id' => 1, // ITS
                'name' => 'Redes y Comunicaciones',
                'code' => 'NT',
                'description' => 'Infraestructura de red, conectividad y comunicaciones',
                'is_active' => true,
                'order' => 3
            ],
            [
                'service_family_id' => 1, // ITS
                'name' => 'Ciberseguridad',
                'code' => 'CY',
                'description' => 'Protección de sistemas, datos y seguridad de la información',
                'is_active' => true,
                'order' => 4
            ],
            [
                'service_family_id' => 1, // ITS
                'name' => 'Infraestructura de Servidores',
                'code' => 'SR',
                'description' => 'Gestión de servidores, virtualización y almacenamiento',
                'is_active' => true,
                'order' => 5
            ],

            // ==================== RECURSOS HUMANOS (HRM) ====================
            [
                'service_family_id' => 2, // HRM
                'name' => 'Reclutamiento y Selección',
                'code' => 'RC',
                'description' => 'Procesos de atracción, selección y contratación de talento',
                'is_active' => true,
                'order' => 1
            ],
            [
                'service_family_id' => 2, // HRM
                'name' => 'Gestión de Nómina',
                'code' => 'PY',
                'description' => 'Administración de nómina, beneficios y compensaciones',
                'is_active' => true,
                'order' => 2
            ],
            [
                'service_family_id' => 2, // HRM
                'name' => 'Desarrollo Organizacional',
                'code' => 'OD',
                'description' => 'Capacitación, desarrollo de talento y planes de carrera',
                'is_active' => true,
                'order' => 3
            ],
            [
                'service_family_id' => 2, // HRM
                'name' => 'Relaciones Laborales',
                'code' => 'RL',
                'description' => 'Gestión de relaciones con empleados y clima organizacional',
                'is_active' => true,
                'order' => 4
            ],

            // ==================== FINANZAS (FIN) ====================
            [
                'service_family_id' => 3, // FIN
                'name' => 'Contabilidad General',
                'code' => 'AC',
                'description' => 'Contabilidad, estados financieros y reportes contables',
                'is_active' => true,
                'order' => 1
            ],
            [
                'service_family_id' => 3, // FIN
                'name' => 'Tesorería',
                'code' => 'TR',
                'description' => 'Gestión de flujo de caja, bancos e inversiones',
                'is_active' => true,
                'order' => 2
            ],
            [
                'service_family_id' => 3, // FIN
                'name' => 'Presupuesto y Costos',
                'code' => 'BU',
                'description' => 'Elaboración de presupuestos y control de costos',
                'is_active' => true,
                'order' => 3
            ],
            [
                'service_family_id' => 3, // FIN
                'name' => 'Auditoría Interna',
                'code' => 'AU',
                'description' => 'Auditorías internas y controles financieros',
                'is_active' => true,
                'order' => 4
            ],

            // ==================== OPERACIONES (OPS) ====================
            [
                'service_family_id' => 4, // OPS
                'name' => 'Gestión de Producción',
                'code' => 'PR',
                'description' => 'Planificación y control de procesos productivos',
                'is_active' => true,
                'order' => 1
            ],
            [
                'service_family_id' => 4, // OPS
                'name' => 'Cadena de Suministro',
                'code' => 'SC',
                'description' => 'Logística, inventarios y gestión de proveedores',
                'is_active' => true,
                'order' => 2
            ],
            [
                'service_family_id' => 4, // OPS
                'name' => 'Calidad de Procesos',
                'code' => 'QP',
                'description' => 'Control de calidad y mejora de procesos operativos',
                'is_active' => true,
                'order' => 3
            ]
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

        $this->command->info('✅ Servicios creados exitosamente');
    }
}
