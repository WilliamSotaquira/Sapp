<?php
// database/seeders/ServiceFamilySeeder.php

namespace Database\Seeders;

use App\Models\ServiceFamily;
use Illuminate\Database\Seeder;

class ServiceFamilySeeder extends Seeder
{
    public function run()
    {
        $families = [
            [
                'name' => 'Tecnología de la Información',
                'code' => 'ITS',
                'description' => 'Servicios de TI, soporte técnico, redes y sistemas',
                'is_active' => true,
                'sort_order' => 1 // Cambiado de 'order' a 'sort_order'
            ],
            [
                'name' => 'Recursos Humanos',
                'code' => 'HRM',
                'description' => 'Gestión de personal, nómina, reclutamiento y desarrollo',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Finanzas y Contabilidad',
                'code' => 'FIN',
                'description' => 'Servicios financieros, contables y de tesorería',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Operaciones',
                'code' => 'OPS',
                'description' => 'Servicios operativos, producción y logística',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Infraestructura y Mantenimiento',
                'code' => 'INF',
                'description' => 'Mantenimiento de instalaciones e infraestructura física',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Marketing y Ventas',
                'code' => 'MKT',
                'description' => 'Servicios de marketing, publicidad y ventas',
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Legal y Cumplimiento',
                'code' => 'LEG',
                'description' => 'Servicios legales y de cumplimiento normativo',
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Calidad y Procesos',
                'code' => 'QUA',
                'description' => 'Gestión de calidad y mejora de procesos',
                'is_active' => true,
                'sort_order' => 8
            ]
        ];

        foreach ($families as $family) {
            ServiceFamily::create($family);
        }

        $this->command->info('✅ Familias de servicio creadas exitosamente');
    }
}
