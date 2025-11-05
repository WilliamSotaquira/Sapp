<?php
// database/seeders/SubServiceSeeder.php

namespace Database\Seeders;

use App\Models\SubService;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubServiceSeeder extends Seeder
{
    public function run()
    {
        // Desactivar verificaciones de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar tabla
        SubService::truncate();

        // Subservicios basados en el archivo Excel
        $subServices = [
            // Servicio 1: Gestión de Contenidos Web y Recursos Digitales
            [
                'name' => 'Error o Problema con Contenido Publicado',
                'code' => 'ERROR_CONTENIDO',
                'description' => 'Reporte de errores o problemas con contenido ya publicado en los portales web',
                'service_id' => 1,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Reorganización de Estructura Web',
                'code' => 'REORG_ESTRUCTURA',
                'description' => 'Reorganización y reestructuración de la arquitectura de información web',
                'service_id' => 1,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Solicitud de Desarrollo de Micrositio Web',
                'code' => 'MICROSITIO_WEB',
                'description' => 'Solicitud para creación y desarrollo de micrositios web especializados',
                'service_id' => 1,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],
            [
                'name' => 'Solicitud de Diseño Gráfico',
                'code' => 'DISENO_GRAFICO',
                'description' => 'Solicitud de servicios de diseño gráfico para contenidos web',
                'service_id' => 1,
                'cost' => 0,
                'is_active' => true,
                'order' => 4
            ],
            [
                'name' => 'Solicitud de Edición o Ajuste de Contenido',
                'code' => 'EDICION_CONTENIDO',
                'description' => 'Solicitud para edición, ajuste o modificación de contenidos web existentes',
                'service_id' => 1,
                'cost' => 0,
                'is_active' => true,
                'order' => 5
            ],

            // Servicio 2: Cumplimiento de Transparencia y Acceso a la Información
            [
                'name' => 'Actualización de Sección de Transparencia',
                'code' => 'ACT_TRANSPARENCIA',
                'description' => 'Actualización de contenidos en las secciones de transparencia y acceso a la información',
                'service_id' => 2,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Asesoría en MIPG y Lineamientos',
                'code' => 'ASESORIA_MIPG',
                'description' => 'Asesoría en Modelo Integrado de Planeación y Gestión y otros lineamientos normativos',
                'service_id' => 2,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Generación de Reportes de MIPG',
                'code' => 'REPORTES_MIPG',
                'description' => 'Generación de reportes y documentación requerida por el MIPG',
                'service_id' => 2,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],
            [
                'name' => 'Publicación por Ley de Transparencia',
                'code' => 'PUB_TRANSPARENCIA',
                'description' => 'Publicación de contenidos requeridos por la Ley de Transparencia y Acceso a la Información',
                'service_id' => 2,
                'cost' => 0,
                'is_active' => true,
                'order' => 4
            ],

            // Servicio 3: Seguimiento de Solicitudes de Publicación
            [
                'name' => 'Consulta de Estado de Solicitud',
                'code' => 'CONSULTA_ESTADO',
                'description' => 'Consulta sobre el estado actual de una solicitud de publicación',
                'service_id' => 3,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Reporte de Demora en Publicación',
                'code' => 'DEMORA_PUBLICACION',
                'description' => 'Reporte de demoras o retrasos en procesos de publicación',
                'service_id' => 3,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Solicitud de Publicación',
                'code' => 'SOL_PUBLICACION',
                'description' => 'Solicitud formal para publicación de contenidos en portales web',
                'service_id' => 3,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],

            // Servicio 4: Administración y Optimización de Sitios Web
            [
                'name' => 'Actualización Masiva de Datos',
                'code' => 'ACT_MASIVA_DATOS',
                'description' => 'Actualización masiva de datos y contenidos en sitios web',
                'service_id' => 4,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Optimización de Estilos y Plantillas',
                'code' => 'OPT_ESTILOS',
                'description' => 'Optimización y mejora de estilos, plantillas y temas de sitios web',
                'service_id' => 4,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Reporte de Inconsistencia en Calidad',
                'code' => 'INCONSISTENCIA_CALIDAD',
                'description' => 'Reporte de inconsistencias o problemas de calidad en sitios web',
                'service_id' => 4,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],

            // Servicio 5: Validación y Monitoreo de Contenidos Web
            [
                'name' => 'Reporte de Enlace Roto o Contenido Obsoleto',
                'code' => 'ENLACE_ROTO',
                'description' => 'Reporte de enlaces rotos o contenidos obsoletos en portales web',
                'service_id' => 5,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Reporte de Error en Contenido Publicado',
                'code' => 'ERROR_PUBLICADO',
                'description' => 'Reporte de errores específicos en contenidos ya publicados',
                'service_id' => 5,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Reportes de Analítica Web',
                'code' => 'ANALITICA_WEB',
                'description' => 'Generación de reportes de analítica web y métricas de desempeño',
                'service_id' => 5,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],
            [
                'name' => 'Solicitud de Eliminación o Retiro de Contenido',
                'code' => 'ELIMINACION_CONTENIDO',
                'description' => 'Solicitud para eliminación o retiro de contenidos específicos',
                'service_id' => 5,
                'cost' => 0,
                'is_active' => true,
                'order' => 4
            ],
            [
                'name' => 'Validación Previa a Publicación',
                'code' => 'VALIDACION_PREVIA',
                'description' => 'Validación y revisión de contenidos antes de su publicación',
                'service_id' => 5,
                'cost' => 0,
                'is_active' => true,
                'order' => 5
            ],

            // Servicio 6: Publicación de Información en Portales Web
            [
                'name' => 'Falla en Proceso de Publicación',
                'code' => 'FALLA_PUBLICACION',
                'description' => 'Reporte de fallas o errores durante el proceso de publicación',
                'service_id' => 6,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Publicación de Documento',
                'code' => 'PUB_DOCUMENTO',
                'description' => 'Publicación de documentos oficiales y archivos en portales web',
                'service_id' => 6,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Publicación de Noticia, PMT o Artículo',
                'code' => 'PUB_NOTICIA',
                'description' => 'Publicación de noticias, artículos o contenidos del PMT',
                'service_id' => 6,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],

            // Servicio 7: Gestión de Disponibilidad y Despliegue del Contratista
            [
                'name' => 'Asignación de Tarea Ad-Hoc',
                'code' => 'TAREA_ADHOC',
                'description' => 'Asignación de tareas específicas y ad-hoc al contratista',
                'service_id' => 7,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Reporte de Indisponibilidad',
                'code' => 'INDISPONIBILIDAD',
                'description' => 'Reporte de indisponibilidad del contratista o servicios',
                'service_id' => 7,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Solicitud de Despliegue en Locación',
                'code' => 'DESPLIEGUE_LOCACION',
                'description' => 'Solicitud de despliegue del contratista en locación específica',
                'service_id' => 7,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],

            // Servicio 8: Otras Actividades Asignadas (8.1)
            [
                'name' => 'Asignación de Tarea No Especificada',
                'code' => 'TAREA_NO_ESPEC',
                'description' => 'Asignación de tareas no especificadas en otros subservicios',
                'service_id' => 8,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Solicitud de Apoyo General',
                'code' => 'APOYO_GENERAL',
                'description' => 'Solicitud de apoyo general no categorizado en otros subservicios',
                'service_id' => 8,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],

            // Servicio 9: Desarrollo de Nuevos Portales y Proyectos Web Especiales (8.2)
            [
                'name' => 'Desarrollo, Configuración e Implementación Técnica',
                'code' => 'DESARROLLO_TECNICO',
                'description' => 'Desarrollo, configuración e implementación técnica de soluciones web',
                'service_id' => 9,
                'cost' => 0,
                'is_active' => true,
                'order' => 1
            ],
            [
                'name' => 'Diseño de Arquitectura de Información y Experiencia de Usuario (UX/UI)',
                'code' => 'DISENO_UX_UI',
                'description' => 'Diseño de arquitectura de información y experiencia de usuario',
                'service_id' => 9,
                'cost' => 0,
                'is_active' => true,
                'order' => 2
            ],
            [
                'name' => 'Plan de Migración y Carga Masiva de Contenido Inicial',
                'code' => 'MIGRACION_CONTENIDO',
                'description' => 'Plan de migración y carga masiva de contenido inicial para nuevos portales',
                'service_id' => 9,
                'cost' => 0,
                'is_active' => true,
                'order' => 3
            ],
            [
                'name' => 'Problema o Incidencia Técnica durante el Desarrollo del Proyecto',
                'code' => 'INCIDENCIA_DESARROLLO',
                'description' => 'Reporte de problemas o incidencias técnicas durante el desarrollo de proyectos',
                'service_id' => 9,
                'cost' => 0,
                'is_active' => true,
                'order' => 4
            ],
            [
                'name' => 'Solicitud de Creación de un Nuevo Portal Web',
                'code' => 'NUEVO_PORTAL',
                'description' => 'Solicitud para creación y desarrollo de un nuevo portal web',
                'service_id' => 9,
                'cost' => 0,
                'is_active' => true,
                'order' => 5
            ]
        ];

        // Contadores para estadísticas
        $createdCount = 0;
        $serviceCounts = [];

        // Crear subservicios
        foreach ($subServices as $subServiceData) {
            // Verificar que el servicio existe
            $service = Service::find($subServiceData['service_id']);

            if (!$service) {
                $this->command->warn("Servicio no encontrado con ID: {$subServiceData['service_id']} para el subservicio: {$subServiceData['name']}");
                continue;
            }

            // Crear el subservicio
            SubService::create([
                'service_id' => $subServiceData['service_id'],
                'name' => $subServiceData['name'],
                'code' => $subServiceData['code'],
                'description' => $subServiceData['description'],
                'cost' => $subServiceData['cost'],
                'is_active' => $subServiceData['is_active'],
                'order' => $subServiceData['order']
            ]);

            $createdCount++;

            // Contar por servicio
            $serviceName = $service->name;
            if (!isset($serviceCounts[$serviceName])) {
                $serviceCounts[$serviceName] = 0;
            }
            $serviceCounts[$serviceName]++;

            $this->command->info("✅ Subservicio creado: {$subServiceData['name']} → {$serviceName}");
        }

        // Reactivar verificaciones de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Mostrar resumen
        $this->command->info('🎉 Subservicios sembrados exitosamente!');
        $this->command->info("📊 Total: {$createdCount} subservicios creados.");

        foreach ($serviceCounts as $serviceName => $count) {
            $this->command->info("   📁 {$serviceName}: {$count} subservicios");
        }
    }
}
