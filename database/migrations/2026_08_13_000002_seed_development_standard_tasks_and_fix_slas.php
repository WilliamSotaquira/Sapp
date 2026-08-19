<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crear StandardTasks para subservicios de desarrollo del contrato nuevo (contrato 2)
     * y ajustar SLAs a tiempos realistas para desarrollo web.
     */
    public function up(): void
    {
        // =====================================================================
        // 1. STANDARD TASKS para SS#190 "Creación de sitios y landings para eventos"
        // =====================================================================
        $ss190Tasks = [
            [
                'sub_service_id' => 190,
                'title' => 'Levantamiento de requerimientos y alcance',
                'description' => 'Reunión o análisis del requerimiento para definir estructura, secciones, funcionalidades y contenido del sitio/landing.',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 2.00,
                'technical_complexity' => 2,
                'environment' => 'development',
                'technical_notes' => 'Definir: secciones, navegación, contenido necesario, fecha de publicación.',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'sub_service_id' => 190,
                'title' => 'Diseño y maquetación de estructura',
                'description' => 'Crear la estructura del sitio/landing según los requerimientos definidos. Incluye wireframe o boceto de secciones.',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 4.00,
                'technical_complexity' => 3,
                'environment' => 'development',
                'technical_notes' => 'Considerar responsive, accesibilidad y lineamientos de Gobierno Digital.',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'sub_service_id' => 190,
                'title' => 'Desarrollo e implementación técnica',
                'description' => 'Construcción del sitio/landing: templates, componentes, integración de contenido multimedia, formularios si aplica.',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 12.00,
                'technical_complexity' => 4,
                'environment' => 'development',
                'technical_notes' => 'Desarrollo en ambiente local/staging. No publicar sin aprobación.',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'sub_service_id' => 190,
                'title' => 'Pruebas y revisión de contenidos',
                'description' => 'Verificar funcionamiento en navegadores, responsive, enlaces, formularios. Validar contenidos con el área solicitante.',
                'type' => 'impact',
                'priority' => 'medium',
                'estimated_hours' => 3.00,
                'technical_complexity' => 2,
                'environment' => 'staging',
                'technical_notes' => 'Probar en Chrome, Firefox, Safari. Validar en móvil.',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'sub_service_id' => 190,
                'title' => 'Despliegue a producción y verificación',
                'description' => 'Publicar el sitio/landing en producción. Verificar URLs, SEO básico, accesibilidad. Informar al solicitante.',
                'type' => 'regular',
                'priority' => 'high',
                'estimated_hours' => 1.50,
                'technical_complexity' => 2,
                'environment' => 'production',
                'technical_notes' => 'Verificar que URLs sean amigables y el sitio sea indexable.',
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($ss190Tasks as $task) {
            DB::table('standard_tasks')->updateOrInsert(
                ['sub_service_id' => $task['sub_service_id'], 'title' => $task['title']],
                array_merge($task, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // =====================================================================
        // 2. STANDARD TASKS para SS#182 "Gestión de Secciones Especiales y Campañas"
        // =====================================================================
        $ss182Tasks = [
            [
                'sub_service_id' => 182,
                'title' => 'Definición de alcance y contenido de la sección/campaña',
                'description' => 'Coordinar con el área solicitante para definir estructura, contenido, plazos y objetivos de la sección especial o campaña.',
                'type' => 'impact',
                'priority' => 'medium',
                'estimated_hours' => 2.00,
                'technical_complexity' => 2,
                'environment' => 'development',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'sub_service_id' => 182,
                'title' => 'Diseño y construcción de la sección/campaña',
                'description' => 'Crear la estructura web, templates y componentes necesarios para la sección especial o campaña digital.',
                'type' => 'impact',
                'priority' => 'high',
                'estimated_hours' => 8.00,
                'technical_complexity' => 3,
                'environment' => 'development',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'sub_service_id' => 182,
                'title' => 'Carga de contenido y validación',
                'description' => 'Incorporar textos, imágenes, documentos y multimedia. Validar con el área solicitante.',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 3.00,
                'technical_complexity' => 1,
                'environment' => 'staging',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'sub_service_id' => 182,
                'title' => 'Publicación y comunicación al solicitante',
                'description' => 'Publicar en producción y confirmar con el área solicitante la disponibilidad.',
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_hours' => 1.00,
                'technical_complexity' => 1,
                'environment' => 'production',
                'is_active' => true,
                'order' => 4,
            ],
        ];

        foreach ($ss182Tasks as $task) {
            DB::table('standard_tasks')->updateOrInsert(
                ['sub_service_id' => $task['sub_service_id'], 'title' => $task['title']],
                array_merge($task, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // =====================================================================
        // 3. AJUSTAR SLAs de SS#190 a tiempos realistas para desarrollo
        //    Resolución: BAJA=30 días, MEDIA=20 días, ALTA=10 días, CRITICA=5 días
        // =====================================================================
        $slaUpdates = [
            ['id' => 633, 'resolution_time_minutes' => 30 * 24 * 60], // BAJA: 30 días
            ['id' => 634, 'resolution_time_minutes' => 20 * 24 * 60], // MEDIA: 20 días
            ['id' => 635, 'resolution_time_minutes' => 10 * 24 * 60], // ALTA: 10 días
            ['id' => 636, 'resolution_time_minutes' => 5 * 24 * 60],  // CRITICA: 5 días
        ];

        foreach ($slaUpdates as $update) {
            DB::table('service_level_agreements')
                ->where('id', $update['id'])
                ->update(['resolution_time_minutes' => $update['resolution_time_minutes']]);
        }

        // =====================================================================
        // 4. AJUSTAR SLAs de SS#182 (Gestión de Secciones Especiales y Campañas)
        //    si tienen tiempos inadecuados
        // =====================================================================
        $ss182SlaIds = DB::table('service_level_agreements as sla')
            ->join('service_subservices as sss', 'sss.id', '=', 'sla.service_subservice_id')
            ->where('sss.sub_service_id', 182)
            ->where('sla.is_active', 1)
            ->pluck('sla.id', 'sla.criticality_level');

        $developmentResolutionMinutes = [
            'BAJA' => 25 * 24 * 60,    // 25 días
            'MEDIA' => 15 * 24 * 60,   // 15 días
            'ALTA' => 8 * 24 * 60,     // 8 días
            'CRITICA' => 4 * 24 * 60,  // 4 días
        ];

        foreach ($ss182SlaIds as $criticality => $slaId) {
            if (isset($developmentResolutionMinutes[$criticality])) {
                DB::table('service_level_agreements')
                    ->where('id', $slaId)
                    ->where('resolution_time_minutes', '<', $developmentResolutionMinutes[$criticality])
                    ->update(['resolution_time_minutes' => $developmentResolutionMinutes[$criticality]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar StandardTasks creados para SS#190
        DB::table('standard_tasks')->where('sub_service_id', 190)->delete();

        // Eliminar StandardTasks creados para SS#182
        DB::table('standard_tasks')
            ->where('sub_service_id', 182)
            ->whereIn('title', [
                'Definición de alcance y contenido de la sección/campaña',
                'Diseño y construcción de la sección/campaña',
                'Carga de contenido y validación',
                'Publicación y comunicación al solicitante',
            ])
            ->delete();

        // Restaurar SLAs originales de SS#190
        $originalSlas = [
            ['id' => 633, 'resolution_time_minutes' => 480],  // 8h
            ['id' => 634, 'resolution_time_minutes' => 360],  // 6h
            ['id' => 635, 'resolution_time_minutes' => 240],  // 4h
            ['id' => 636, 'resolution_time_minutes' => 120],  // 2h
        ];

        foreach ($originalSlas as $sla) {
            DB::table('service_level_agreements')
                ->where('id', $sla['id'])
                ->update(['resolution_time_minutes' => $sla['resolution_time_minutes']]);
        }
    }
};
