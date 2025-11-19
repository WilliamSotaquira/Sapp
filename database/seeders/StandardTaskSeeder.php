<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubService;
use App\Models\StandardTask;
use App\Models\StandardSubtask;
use Illuminate\Support\Facades\DB;

class StandardTaskSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        StandardSubtask::truncate();
        StandardTask::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Obtener subservicios comunes
        $errorContenido = SubService::where('code', 'ERROR_CONTENIDO')->first();
        $actualizacionContenido = SubService::where('code', 'ACT_CONTENIDO')->first();
        $solPublicacion = SubService::where('code', 'SOL_PUBLICACION')->first();
        $desarrolloTecnico = SubService::where('code', 'DESARROLLO_TECNICO')->first();

        // =====================================================================
        // Error o Problema con Contenido Publicado
        // =====================================================================
        if ($errorContenido) {
            $task1 = StandardTask::create([
                'sub_service_id' => $errorContenido->id,
                'title' => 'Diagnóstico del error reportado',
                'description' => 'Identificar y documentar el error en el contenido publicado',
                'type' => 'regular',
                'priority' => 'high',
                'estimated_hours' => 0.5,
                'order' => 1,
            ]);

            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Revisar contenido afectado', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Documentar error con capturas', 'priority' => 'high', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Identificar causa raíz', 'priority' => 'medium', 'order' => 3]);

            $task2 = StandardTask::create([
                'sub_service_id' => $errorContenido->id,
                'title' => 'Corrección del contenido',
                'description' => 'Aplicar correcciones necesarias al contenido',
                'type' => 'regular',
                'priority' => 'high',
                'estimated_hours' => 1.0,
                'order' => 2,
            ]);

            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Realizar corrección', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Verificar en ambiente de prueba', 'priority' => 'high', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Publicar corrección', 'priority' => 'high', 'order' => 3]);

            $task3 = StandardTask::create([
                'sub_service_id' => $errorContenido->id,
                'title' => 'Verificación y cierre',
                'description' => 'Verificar que el error ha sido corregido completamente',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 0.5,
                'order' => 3,
            ]);

            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Verificar en producción', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Notificar al solicitante', 'priority' => 'medium', 'order' => 2]);
        }

        // =====================================================================
        // Actualización de Contenido Existente
        // =====================================================================
        if ($actualizacionContenido) {
            $task1 = StandardTask::create([
                'sub_service_id' => $actualizacionContenido->id,
                'title' => 'Análisis de contenido a actualizar',
                'description' => 'Revisar contenido actual y cambios solicitados',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 0.5,
                'order' => 1,
            ]);

            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Revisar contenido actual', 'priority' => 'medium', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Validar nuevos contenidos', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Crear backup del contenido actual', 'priority' => 'high', 'order' => 3]);

            $task2 = StandardTask::create([
                'sub_service_id' => $actualizacionContenido->id,
                'title' => 'Actualización del contenido',
                'description' => 'Realizar las actualizaciones solicitadas',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 1.5,
                'order' => 2,
            ]);

            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Aplicar cambios de contenido', 'priority' => 'medium', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Actualizar imágenes/multimedia', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Verificar formato y estilos', 'priority' => 'medium', 'order' => 3]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Pruebas de visualización', 'priority' => 'high', 'order' => 4]);

            $task3 = StandardTask::create([
                'sub_service_id' => $actualizacionContenido->id,
                'title' => 'Publicación y verificación',
                'description' => 'Publicar y verificar los cambios',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 0.5,
                'order' => 3,
            ]);

            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Publicar en producción', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Verificar en múltiples dispositivos', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Confirmar con solicitante', 'priority' => 'medium', 'order' => 3]);
        }

        // =====================================================================
        // Solicitud de Publicación
        // =====================================================================
        if ($solPublicacion) {
            $task1 = StandardTask::create([
                'sub_service_id' => $solPublicacion->id,
                'title' => 'Revisión de contenido a publicar',
                'description' => 'Validar contenido y requisitos de publicación',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 0.5,
                'order' => 1,
            ]);

            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Validar contenido recibido', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Verificar formato y calidad', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Solicitar correcciones si necesario', 'priority' => 'medium', 'order' => 3]);

            $task2 = StandardTask::create([
                'sub_service_id' => $solPublicacion->id,
                'title' => 'Preparación y publicación',
                'description' => 'Preparar y publicar el contenido',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 1.0,
                'order' => 2,
            ]);

            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Formatear contenido', 'priority' => 'medium', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Optimizar imágenes', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Configurar metadatos SEO', 'priority' => 'low', 'order' => 3]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Publicar contenido', 'priority' => 'high', 'order' => 4]);

            $task3 = StandardTask::create([
                'sub_service_id' => $solPublicacion->id,
                'title' => 'Verificación post-publicación',
                'description' => 'Verificar correcta publicación del contenido',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 0.25,
                'order' => 3,
            ]);

            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Verificar visualización', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Probar enlaces', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Notificar publicación', 'priority' => 'medium', 'order' => 3]);
        }

        // =====================================================================
        // Desarrollo Técnico
        // =====================================================================
        if ($desarrolloTecnico) {
            $task1 = StandardTask::create([
                'sub_service_id' => $desarrolloTecnico->id,
                'title' => 'Análisis de requerimientos',
                'description' => 'Analizar y documentar requerimientos técnicos',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 2.0,
                'order' => 1,
            ]);

            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Reunión con solicitante', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Documentar requerimientos', 'priority' => 'high', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task1->id, 'title' => 'Estimar tiempo y recursos', 'priority' => 'medium', 'order' => 3]);

            $task2 = StandardTask::create([
                'sub_service_id' => $desarrolloTecnico->id,
                'title' => 'Diseño de solución',
                'description' => 'Diseñar arquitectura y componentes',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 3.0,
                'order' => 2,
            ]);

            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Diseñar arquitectura', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Crear diagramas técnicos', 'priority' => 'medium', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task2->id, 'title' => 'Definir tecnologías', 'priority' => 'high', 'order' => 3]);

            $task3 = StandardTask::create([
                'sub_service_id' => $desarrolloTecnico->id,
                'title' => 'Implementación',
                'description' => 'Desarrollar la solución técnica',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 8.0,
                'order' => 3,
            ]);

            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Configurar ambiente desarrollo', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Desarrollar funcionalidad', 'priority' => 'high', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Implementar pruebas unitarias', 'priority' => 'medium', 'order' => 3]);
            StandardSubtask::create(['standard_task_id' => $task3->id, 'title' => 'Documentar código', 'priority' => 'low', 'order' => 4]);

            $task4 = StandardTask::create([
                'sub_service_id' => $desarrolloTecnico->id,
                'title' => 'Pruebas y despliegue',
                'description' => 'Realizar pruebas y desplegar en producción',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 2.0,
                'order' => 4,
            ]);

            StandardSubtask::create(['standard_task_id' => $task4->id, 'title' => 'Pruebas funcionales', 'priority' => 'high', 'order' => 1]);
            StandardSubtask::create(['standard_task_id' => $task4->id, 'title' => 'Pruebas de integración', 'priority' => 'high', 'order' => 2]);
            StandardSubtask::create(['standard_task_id' => $task4->id, 'title' => 'Desplegar en producción', 'priority' => 'high', 'order' => 3]);
            StandardSubtask::create(['standard_task_id' => $task4->id, 'title' => 'Verificar en producción', 'priority' => 'high', 'order' => 4]);
        }

        $this->command->info('✅ Tareas estándar creadas exitosamente');
    }
}
