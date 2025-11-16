<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use Carbon\Carbon;

class ProjectsSeeder extends Seeder
{
    public function run()
    {
        $projects = [
            [
                'name' => 'Sistema de Gestión Documental',
                'code' => 'SGD-2025',
                'status' => 'active'
            ],
            [
                'name' => 'Portal de Autoservicio',
                'code' => 'PAS-2025',
                'status' => 'in_progress'
            ],
            [
                'name' => 'Actualización ERP',
                'code' => 'ERP-2025',
                'status' => 'active'
            ],
            [
                'name' => 'App Móvil de Servicios',
                'code' => 'AMS-2025',
                'status' => 'active'
            ],
            [
                'name' => 'Infraestructura Cloud',
                'code' => 'IC-2025',
                'status' => 'in_progress'
            ],
            [
                'name' => 'Renovación Red Corporativa',
                'code' => 'RRC-2025',
                'status' => 'active'
            ],
            [
                'name' => 'Sistema de Business Intelligence',
                'code' => 'SBI-2025',
                'status' => 'in_progress'
            ],
            [
                'name' => 'Plataforma E-learning',
                'code' => 'PEL-2025',
                'status' => 'active'
            ]
        ];

        foreach ($projects as $projectData) {
            Project::create($projectData);
        }

        $this->command->info('✅ Proyectos de ejemplo creados: ' . Project::count());
    }
}
