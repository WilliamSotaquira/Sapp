<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migra las solicitudes existentes de Cultura desde los subservicios antiguos
 * a los nuevos subservicios alineados al contrato 0813-2026.
 *
 * Mapeo basado en nombre del subservicio antiguo → código del subservicio nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $contract = DB::table('contracts')->where('number', '0813-2026')->first();
        if (!$contract) {
            return;
        }

        $companyId = $contract->company_id;

        // Mapeo: nombre del subservicio antiguo → código del subservicio nuevo
        $mapping = [
            // Familia 1: Publicaciones
            'Actualización de Contenidos en Portal Principal' => 'CUL_F1_ACT_PORTAL',
            'Actualización de Secciones del Portal Principal' => 'CUL_F1_ACT_PORTAL',
            'Publicación de Contenidos en Landing Pages' => 'CUL_F1_PUB_NOTICIA',
            'Mantenimiento de Páginas y Micrositios' => 'CUL_F1_ACT_PORTAL',
            'Mantenimiento de Micrositios' => 'CUL_F1_ACT_PORTAL',
            'Mantenimiento de Micrositios y Subdominios' => 'CUL_F1_ACT_PORTAL',

            // Familia 2: Estrategia de comunicación digital
            'Ejecución de envío de comunicaciones masivas' => 'CUL_F2_COM_MASIVAS',
            'Gestión de Secciones Especiales' => 'CUL_F2_SECC_CAMPANAS',
            'Soporte para Mailing y Comunicaciones' => 'CUL_F2_COM_MASIVAS',
            'Asesoría Técnica en Innovación y Tendencias Digitales para Comunicación' => 'CUL_F2_REP_ANALITICA',

            // Familia 3: Parrilla y estadísticas
            'Gestión de Parrilla de Contenidos' => 'CUL_F3_REG_GESTION',
            'Informes Trimestrales Digitales' => 'CUL_F3_INF_ESTADISTICAS',
            'Generación de Reportes de Estadísticas Básicas' => 'CUL_F3_INF_ESTADISTICAS',
            'Análisis de Temáticas y Formatos más Efectivos' => 'CUL_F3_INF_ESTADISTICAS',
            'Elaboración de Informes Ejecutivos de Resultados' => 'CUL_F3_INF_ESTADISTICAS',
            'Reportes para Mantenimiento' => 'CUL_F3_INF_ESTADISTICAS',

            // Familia 4: Gobierno Digital
            'Implementación de Componentes de Transparencia y Acceso a la Información' => 'CUL_F4_ACT_TRANSPARENCIA',
            'Análisis de Tráfico, Usabilidad y Comportamiento Web (Google Analytics, Heatmaps)' => 'CUL_F4_ACCESIBILIDAD',
            'Auditoría y Aseguramiento de Cumplimiento MINTIC' => 'CUL_F4_ACCESIBILIDAD',

            // Familia 5: Eventos
            'Desarrollo de Landing Pages Temáticas' => 'CUL_F5_LANDING_EVENTOS',
            'Arquitectura de Contenido para Nuevos Sitios' => 'CUL_F5_LANDING_EVENTOS',
            'Cobertura para Redes Sociales en Tiempo Real' => 'CUL_F5_CONT_EVENTOS',
            'Desarrollo y Publicación de Sitios Web y Landings (HTML/CSS/JS)' => 'CUL_F5_LANDING_EVENTOS',

            // Familia 6: Reuniones
            'Coordinación de Actualizaciones por Área' => 'CUL_F6_REUNION_VALID',
            'Coordinación Técnica con Infraestructura TI' => 'CUL_F6_REUNION_VALID',
            'Coordinación de Contenidos Digitales' => 'CUL_F6_REUNION_VALID',

            // Familia 7: Confidencialidad
            // (sin mapeo directo — no hay solicitudes antiguas en esta categoría)

            // Familia 8: Demás actividades
            'Gestión Técnica de Aplicativos Web' => 'CUL_F8_CORREC_URGENTES',
            'Gestión de Servidores y Subdominios' => 'CUL_F8_CORREC_URGENTES',
            'Desarrollo de Aplicativos Web Interactivos' => 'CUL_F8_TAREA_NO_ESPEC',
            'Pruebas y Desarrollo Experimental' => 'CUL_F8_TAREA_NO_ESPEC',
            'Soporte a Proyectos Especiales No Recurrentes' => 'CUL_F8_TAREA_NO_ESPEC',
            'Resolución de Incidencias Técnicas Web' => 'CUL_F8_CORREC_URGENTES',
            'Optimización de Código y Rendimiento' => 'CUL_F8_TAREA_NO_ESPEC',
            'Desarrollo de Nuevas Funcionalidades Web' => 'CUL_F8_TAREA_NO_ESPEC',
            'Gestión de Archivos en Servidores Web' => 'CUL_F8_TAREA_NO_ESPEC',
            'Soporte Técnico al Grupo de Divulgación y Prensa' => 'CUL_F8_TAREA_ADMIN',
            'Gestión de Hojas de Vida y Actos Administrativos' => 'CUL_F8_TAREA_ADMIN',
            'Acompañamiento en Evaluaciones o Auditorías Adicionales' => 'CUL_F8_CAPACITACIONES',
        ];

        // Resolver IDs de subservicios nuevos por código
        $newSubServiceIds = DB::table('sub_services')
            ->where('code', 'LIKE', 'CUL_F%')
            ->where('is_active', true)
            ->pluck('id', 'code')
            ->toArray();

        // Obtener IDs de familias inactivas del contrato
        $inactiveFamilyIds = DB::table('service_families')
            ->where('contract_id', $contract->id)
            ->where('is_active', false)
            ->pluck('id')
            ->toArray();

        // Obtener subservicios antiguos (de familias inactivas)
        $oldSubServices = DB::table('sub_services')
            ->join('services', 'sub_services.service_id', '=', 'services.id')
            ->whereIn('services.service_family_id', $inactiveFamilyIds)
            ->select('sub_services.id', 'sub_services.name')
            ->get();

        $migrated = 0;
        $skipped = 0;
        $unmapped = [];

        foreach ($oldSubServices as $oldSS) {
            $newCode = $mapping[$oldSS->name] ?? null;

            if (!$newCode) {
                // Intentar fallback al subservicio genérico
                $newCode = 'CUL_F8_TAREA_NO_ESPEC';
            }

            $newId = $newSubServiceIds[$newCode] ?? null;

            if (!$newId) {
                $unmapped[] = $oldSS->name;
                continue;
            }

            // Actualizar solicitudes que usan este subservicio antiguo (solo de esta empresa)
            $updated = DB::table('service_requests')
                ->where('sub_service_id', $oldSS->id)
                ->where('company_id', $companyId)
                ->update([
                    'sub_service_id' => $newId,
                    'updated_at' => now(),
                ]);

            $migrated += $updated;
            if ($updated === 0) {
                $skipped++;
            }
        }

        Log::info("Migración de solicitudes Cultura completada.", [
            'migrated' => $migrated,
            'skipped_no_requests' => $skipped,
            'unmapped_subservices' => $unmapped,
        ]);
    }

    public function down(): void
    {
        Log::info('Rollback: Use el backup JSON + export para restaurar sub_service_id originales.');
    }
};
