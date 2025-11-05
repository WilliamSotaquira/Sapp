<?php
// database/seeders/ServiceSeeder.php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceFamily;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        // Desactivar verificaciones de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar tabla
        Service::truncate();

        // Mapeo de familias de servicio basado en las descripciones
        $familyMappings = [
            '1. Apoyar en la edición, diseño y organización de contenidos web y otros recursos relacionados.' => 'Gestión de Contenidos Web',
            '2. Apoyar e implementar acciones que faciliten el cumplimento de los lineamientos del Modelo Integrado de Planeación y Gestión, la Ley 1712 de 2014 y el Decreto' => 'Cumplimiento Normativo',
            '3. Realizar el seguimiento de solicitudes de publicación en la página web, intranet y otros portales web de la secretaría.' => 'Seguimiento de Publicaciones',
            '4. Apoyar en la administración y optimización de estilo, calidad y actualización de datos de los sitios web de la SDM.' => 'Administración de Sitios Web',
            '5. Validar y monitorear contenidos publicados en los portales Web de la SDM.' => 'Validación de Contenidos Web',
            '6. Apoyar la publicación de información en la web, intranet y sitios web de la SDM.' => 'Publicación de Información',
            '7. Contar con disponibilidad para prestar sus servicios, de acuerdo con su especialidad, en los espacios acordados y requeridos por el supervisor según la necesidad del servicio.' => 'Disponibilidad de Servicios',
            '8. Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.' => 'Tareas Asignadas por Supervisor'
        ];

        // Servicios basados en el archivo Excel
        $services = [
            [
                'name' => '1. Gestión de Contenidos Web y Recursos Digitales',
                'code' => 'GEST_CONT_WEB',
                'description' => 'Apoyar en la edición, diseño y organización de contenidos web y otros recursos relacionados.',
                'family_description' => '1. Apoyar en la edición, diseño y organización de contenidos web y otros recursos relacionados.',
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => '2. Cumplimiento de Transparencia y Acceso a la Información',
                'code' => 'CUMPL_TRANS',
                'description' => 'Apoyar e implementar acciones que faciliten el cumplimento de los lineamientos del Modelo Integrado de Planeación y Gestión, la Ley 1712 de 2014 y el Decreto',
                'family_description' => '2. Apoyar e implementar acciones que faciliten el cumplimento de los lineamientos del Modelo Integrado de Planeación y Gestión, la Ley 1712 de 2014 y el Decreto',
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => '3. Seguimiento de Solicitudes de Publicación',
                'code' => 'SEG_SOL_PUB',
                'description' => 'Realizar el seguimiento de solicitudes de publicación en la página web, intranet y otros portales web de la secretaría.',
                'family_description' => '3. Realizar el seguimiento de solicitudes de publicación en la página web, intranet y otros portales web de la secretaría.',
                'is_active' => true,
                'order' => 3
            ],
            [
                'name' => '4. Administración y Optimización de Sitios Web',
                'code' => 'ADMIN_OPT_WEB',
                'description' => 'Apoyar en la administración y optimización de estilo, calidad y actualización de datos de los sitios web de la SDM.',
                'family_description' => '4. Apoyar en la administración y optimización de estilo, calidad y actualización de datos de los sitios web de la SDM.',
                'is_active' => true,
                'order' => 4
            ],
            [
                'name' => '5. Validación y Monitoreo de Contenidos Web',
                'code' => 'VAL_MON_CONT',
                'description' => 'Validar y monitorear contenidos publicados en los portales Web de la SDM.',
                'family_description' => '5. Validar y monitorear contenidos publicados en los portales Web de la SDM.',
                'is_active' => true,
                'order' => 5
            ],
            [
                'name' => '6. Publicación de Información en Portales Web',
                'code' => 'PUB_INFO_WEB',
                'description' => 'Apoyar la publicación de información en la web, intranet y sitios web de la SDM.',
                'family_description' => '6. Apoyar la publicación de información en la web, intranet y sitios web de la SDM.',
                'is_active' => true,
                'order' => 6
            ],
            [
                'name' => '7. Gestión de Disponibilidad y Despliegue del Contratista',
                'code' => 'GEST_DISP_CON',
                'description' => 'Contar con disponibilidad para prestar sus servicios, de acuerdo con su especialidad, en los espacios acordados y requeridos por el supervisor según la necesidad del servicio.',
                'family_description' => '7. Contar con disponibilidad para prestar sus servicios, de acuerdo con su especialidad, en los espacios acordados y requeridos por el supervisor según la necesidad del servicio.',
                'is_active' => true,
                'order' => 7
            ],
            [
                'name' => '8.1. Otras Actividades Asignadas',
                'code' => 'OTRAS_ACT',
                'description' => 'Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',
                'family_description' => '8. Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',
                'is_active' => true,
                'order' => 8
            ],
            [
                'name' => '8.2. Desarrollo de Nuevos Portales y Proyectos Web Especiales',
                'code' => 'DES_PORT_ESP',
                'description' => 'Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',
                'family_description' => '8. Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',
                'is_active' => true,
                'order' => 9
            ]
        ];

        // Crear servicios
        foreach ($services as $serviceData) {
            // Buscar la familia de servicio
            $familyName = $familyMappings[$serviceData['family_description']] ?? null;
            $family = ServiceFamily::where('name', $familyName)->first();

            if (!$family) {
                $this->command->warn("Familia no encontrada: {$familyName} para el servicio: {$serviceData['name']}");
                continue;
            }

            // Crear el servicio
            Service::create([
                'name' => $serviceData['name'],
                'code' => $serviceData['code'],
                'description' => $serviceData['description'],
                'service_family_id' => $family->id,
                'is_active' => $serviceData['is_active'],
                'order' => $serviceData['order']
            ]);

            $this->command->info("✅ Servicio creado: {$serviceData['name']}");
        }

        // Reactivar verificaciones de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🎉 Servicios sembrados exitosamente!');
        $this->command->info('📊 Total: ' . count($services) . ' servicios creados.');
    }
}
