<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuickDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Insertando datos de prueba rápidos...');

        // Clasificaciones
        if (DB::table('classifications')->count() == 0) {
            DB::table('classifications')->insert([
                ['name' => 'Soporte Técnico', 'color' => '#007bff', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Desarrollo', 'color' => '#28a745', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Mantenimiento', 'color' => '#ffc107', 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('✅ Clasificaciones creadas');
        }

        // Reportadores
        if (DB::table('reporters')->count() == 0) {
            DB::table('reporters')->insert([
                [
                    'name' => 'Administrador Sistema',
                    'email' => 'admin@sdm.gob',
                    'department' => 'Tecnologías de la Información',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Usuario Demo',
                    'email' => 'demo@sdm.gob',
                    'department' => 'Recursos Humanos',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);
            $this->command->info('✅ Reportadores creados');
        }

        // Proyectos
        if (DB::table('projects')->count() == 0) {
            DB::table('projects')->insert([
                [
                    'name' => 'Portal Web Gubernamental',
                    'code' => 'PORTAL-2024',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Sistema de Gestión Documental',
                    'code' => 'DOCS-2024',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);
            $this->command->info('✅ Proyectos creados');
        }

        // Requerimientos
        if (DB::table('requirements')->count() == 0) {
            DB::table('requirements')->insert([
                [
                    'title' => 'Configuración inicial del sistema SDM',
                    'description' => 'Configurar todos los módulos del sistema de gestión de requerimientos',
                    'code' => 'REQ-' . time(),
                    'reporter_id' => 1,
                    'classification_id' => 1,
                    'project_id' => 1,
                    'priority' => 'high',
                    'status' => 'completed',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'title' => 'Implementar módulo de reportes',
                    'description' => 'Desarrollar el módulo de generación de reportes estadísticos',
                    'code' => 'REQ-' . (time() + 1),
                    'reporter_id' => 2,
                    'classification_id' => 2,
                    'project_id' => 1,
                    'priority' => 'medium',
                    'status' => 'in_progress',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'title' => 'Corregir error en login',
                    'description' => 'Solucionar problema de autenticación en algunos navegadores',
                    'code' => 'REQ-' . (time() + 2),
                    'reporter_id' => 1,
                    'classification_id' => 3,
                    'project_id' => 2,
                    'priority' => 'urgent',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);
            $this->command->info('✅ Requerimientos creados');
        }

        // Alertas
        if (DB::table('alerts')->count() == 0) {
            DB::table('alerts')->insert([
                [
                    'title' => 'Sistema Configurado Exitosamente',
                    'message' => 'El sistema SDM ha sido configurado y está listo para su uso.',
                    'type' => 'success',
                    'alert_date' => now(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'title' => 'Mantenimiento Programado',
                    'message' => 'El sistema estará en mantenimiento el próximo sábado de 2:00 AM a 6:00 AM.',
                    'type' => 'info',
                    'alert_date' => now()->addDays(1),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);
            $this->command->info('✅ Alertas creadas');
        }

        $this->command->info('🎉 Todos los datos de prueba han sido insertados exitosamente!');
        $this->command->info('📊 Resumen:');
        $this->command->info('   - Clasificaciones: ' . DB::table('classifications')->count());
        $this->command->info('   - Reportadores: ' . DB::table('reporters')->count());
        $this->command->info('   - Proyectos: ' . DB::table('projects')->count());
        $this->command->info('   - Requerimientos: ' . DB::table('requirements')->count());
        $this->command->info('   - Alertas: ' . DB::table('alerts')->count());
    }
}
