<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceFamily;
use Illuminate\Support\Facades\DB;

class ServiceFamilySeederManual extends Seeder
{
    public function run()
    {
        // Limpiar tabla primero
        DB::table('service_families')->delete();

        $families = [
            [
                'name' => 'Soporte Técnico',
                'code' => 'SOP',
                'description' => 'Servicios de soporte técnico y asistencia',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Desarrollo',
                'code' => 'DES',
                'description' => 'Servicios de desarrollo de software',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Infraestructura',
                'code' => 'INF',
                'description' => 'Servicios de infraestructura TI',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Consultoría',
                'code' => 'CON',
                'description' => 'Servicios de consultoría tecnológica',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Capacitación',
                'code' => 'CAP',
                'description' => 'Servicios de capacitación y entrenamiento',
                'is_active' => true,
                'sort_order' => 5
            ]
        ];

        foreach ($families as $family) {
            ServiceFamily::create($family);
        }

        $this->command->info('✅ Familias de servicio creadas manualmente!');
    }
}
