<?php
// database/seeders/ServiceFamilySeeder.php

namespace Database\Seeders;

use App\Models\ServiceFamily;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceFamilySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos de las familias de servicios con códigos más cortos
        $serviceFamilies = [
            [
                'name' => 'Gestión de Contenidos Web',
                'code' => 'WEB_CONTENT',
                'description' => 'Apoyar en la edición, diseño y organización de contenidos web y otros recursos relacionados.',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Cumplimiento Normativo',
                'code' => 'COMPLIANCE',
                'description' => 'Apoyar e implementar acciones que faciliten el cumplimento de los lineamientos del Modelo Integrado de Planeación y Gestión, la Ley 1712 de 2014 y el Decreto',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Seguimiento de Publicaciones',
                'code' => 'PUB_TRACKING',
                'description' => 'Realizar el seguimiento de solicitudes de publicación en la página web, intranet y otros portales web de la secretaría.',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Administración de Sitios Web',
                'code' => 'WEB_ADMIN',
                'description' => 'Apoyar en la administración y optimización de estilo, calidad y actualización de datos de los sitios web de la SDM.',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Validación de Contenidos Web',
                'code' => 'CONT_VALID',
                'description' => 'Validar y monitorear contenidos publicados en los portales Web de la SDM.',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Publicación de Información',
                'code' => 'INFO_PUB',
                'description' => 'Apoyar la publicación de información en la web, intranet y sitios web de la SDM.',
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Disponibilidad de Servicios',
                'code' => 'SERV_AVAIL',
                'description' => 'Contar con disponibilidad para prestar sus servicios, de acuerdo con su especialidad, en los espacios acordados y requeridos por el supervisor según la necesidad del servicio.',
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Tareas Asignadas por Supervisor',
                'code' => 'SUPER_TASKS',
                'description' => 'Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',
                'is_active' => true,
                'sort_order' => 8
            ]
        ];

        // Desactivar las verificaciones de claves foráneas temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar la tabla antes de sembrar
        ServiceFamily::truncate();

        // Insertar los datos
        foreach ($serviceFamilies as $family) {
            ServiceFamily::create($family);
        }

        // Reactivar las verificaciones de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Familias de servicios sembradas exitosamente!');
        $this->command->info('Total: ' . count($serviceFamilies) . ' familias creadas.');
    }
}
