<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Technician;
use App\Models\TechnicianSkill;
use App\Models\CapacityRule;
use Illuminate\Support\Facades\Hash;

class TechnicianModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios técnicos de ejemplo
        $technicians = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@example.com',
                'experience_level' => 'senior',
                'specialties' => ['Backend', 'Laravel', 'PHP', 'API Development'],
                'skills' => [
                    ['skill_name' => 'Laravel', 'proficiency_level' => 'expert', 'years_experience' => 5, 'is_primary' => true],
                    ['skill_name' => 'PHP', 'proficiency_level' => 'expert', 'years_experience' => 6, 'is_primary' => false],
                    ['skill_name' => 'MySQL', 'proficiency_level' => 'advanced', 'years_experience' => 5, 'is_primary' => false],
                    ['skill_name' => 'API REST', 'proficiency_level' => 'expert', 'years_experience' => 4, 'is_primary' => false],
                ],
            ],
            [
                'name' => 'María García',
                'email' => 'maria.garcia@example.com',
                'experience_level' => 'senior',
                'specialties' => ['Frontend', 'React', 'Vue.js', 'UI/UX'],
                'skills' => [
                    ['skill_name' => 'React', 'proficiency_level' => 'expert', 'years_experience' => 4, 'is_primary' => true],
                    ['skill_name' => 'Vue.js', 'proficiency_level' => 'advanced', 'years_experience' => 3, 'is_primary' => false],
                    ['skill_name' => 'JavaScript', 'proficiency_level' => 'expert', 'years_experience' => 5, 'is_primary' => false],
                    ['skill_name' => 'CSS', 'proficiency_level' => 'advanced', 'years_experience' => 5, 'is_primary' => false],
                ],
            ],
            [
                'name' => 'Carlos Rodríguez',
                'email' => 'carlos.rodriguez@example.com',
                'experience_level' => 'mid',
                'specialties' => ['Fullstack', 'Laravel', 'Vue.js', 'Database'],
                'skills' => [
                    ['skill_name' => 'Laravel', 'proficiency_level' => 'advanced', 'years_experience' => 3, 'is_primary' => true],
                    ['skill_name' => 'Vue.js', 'proficiency_level' => 'intermediate', 'years_experience' => 2, 'is_primary' => false],
                    ['skill_name' => 'JavaScript', 'proficiency_level' => 'advanced', 'years_experience' => 3, 'is_primary' => false],
                    ['skill_name' => 'PostgreSQL', 'proficiency_level' => 'intermediate', 'years_experience' => 2, 'is_primary' => false],
                ],
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'ana.martinez@example.com',
                'experience_level' => 'mid',
                'specialties' => ['DevOps', 'Infraestructura', 'CI/CD', 'Docker'],
                'skills' => [
                    ['skill_name' => 'Docker', 'proficiency_level' => 'advanced', 'years_experience' => 3, 'is_primary' => true],
                    ['skill_name' => 'Linux', 'proficiency_level' => 'expert', 'years_experience' => 4, 'is_primary' => false],
                    ['skill_name' => 'CI/CD', 'proficiency_level' => 'advanced', 'years_experience' => 3, 'is_primary' => false],
                    ['skill_name' => 'AWS', 'proficiency_level' => 'intermediate', 'years_experience' => 2, 'is_primary' => false],
                ],
            ],
            [
                'name' => 'Luis Fernández',
                'email' => 'luis.fernandez@example.com',
                'experience_level' => 'junior',
                'specialties' => ['Frontend', 'JavaScript', 'HTML', 'CSS'],
                'skills' => [
                    ['skill_name' => 'JavaScript', 'proficiency_level' => 'intermediate', 'years_experience' => 1, 'is_primary' => true],
                    ['skill_name' => 'HTML/CSS', 'proficiency_level' => 'advanced', 'years_experience' => 2, 'is_primary' => false],
                    ['skill_name' => 'React', 'proficiency_level' => 'beginner', 'years_experience' => 1, 'is_primary' => false],
                ],
            ],
        ];

        foreach ($technicians as $techData) {
            // Crear usuario
            $user = User::firstOrCreate(
                ['email' => $techData['email']],
                [
                    'name' => $techData['name'],
                    'password' => Hash::make('password123'),
                ]
            );

            // Crear técnico
            $technician = Technician::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialties' => $techData['specialties'],
                    'experience_level' => $techData['experience_level'],
                    'remote_available' => true,
                    'work_start_time' => '08:00',
                    'work_end_time' => '17:00',
                    'status' => 'active',
                    'daily_capacity_minutes' => 480, // 8 horas
                    'max_concurrent_tasks' => 1,
                ]
            );

            // Crear skills
            foreach ($techData['skills'] as $skillData) {
                TechnicianSkill::firstOrCreate(
                    [
                        'technician_id' => $technician->id,
                        'skill_name' => $skillData['skill_name'],
                    ],
                    $skillData
                );
            }

            // Crear regla de capacidad por defecto
            CapacityRule::firstOrCreate(
                [
                    'technician_id' => $technician->id,
                    'day_type' => 'weekday',
                ],
                [
                    'max_impact_tasks_morning' => 2,
                    'max_regular_tasks_afternoon' => 6,
                    'impact_task_duration_minutes' => 90,
                    'regular_task_duration_minutes' => 25,
                    'buffer_between_tasks_minutes' => 5,
                    'documentation_time_minutes' => 30,
                    'is_active' => true,
                ]
            );

            $this->command->info("Técnico creado: {$user->name}");
        }

        // Crear regla de capacidad global
        CapacityRule::firstOrCreate(
            [
                'technician_id' => null,
                'day_type' => 'weekday',
            ],
            [
                'max_impact_tasks_morning' => 2,
                'max_regular_tasks_afternoon' => 6,
                'impact_task_duration_minutes' => 90,
                'regular_task_duration_minutes' => 25,
                'buffer_between_tasks_minutes' => 5,
                'documentation_time_minutes' => 30,
                'is_active' => true,
            ]
        );

        $this->command->info('Módulo de Técnicos inicializado correctamente!');
        $this->command->info('Usuarios creados con contraseña: password123');
    }
}
