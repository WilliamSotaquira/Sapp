<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconstruye la estructura del contrato 0813-2026 (Cultura) para que tenga
 * exactamente 8 familias, una por cada obligación contractual.
 *
 * Pasos:
 * 1. Desactiva las familias anteriores (no las elimina por integridad referencial).
 * 2. Crea 8 familias nuevas alineadas 1:1 con las obligaciones.
 * 3. Crea servicios y subservicios bajo cada familia.
 * 4. Crea las relaciones pivote (service_subservices).
 *
 * IMPORTANTE: Las solicitudes existentes siguen vinculadas a los subservicios anteriores.
 * El reasignamiento se hace por separado si es necesario.
 */
return new class extends Migration
{
    public function up(): void
    {
        $contract = DB::table('contracts')->where('number', '0813-2026')->first();

        if (!$contract) {
            Log::warning('Migración rebuild cultura: No se encontró el contrato 0813-2026.');
            return;
        }

        $company = DB::table('companies')->where('id', $contract->company_id)->first();
        if (!$company || !str_contains(mb_strtolower($company->name), 'cultura')) {
            Log::warning('Migración rebuild cultura: El contrato no pertenece a Cultura.');
            return;
        }

        // Paso 1: Desactivar familias anteriores del contrato
        DB::table('service_families')
            ->where('contract_id', $contract->id)
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Paso 2: Desactivar subservicios creados por la migración anterior (por prefijo CUL_)
        DB::table('sub_services')
            ->where('code', 'LIKE', 'CUL_%')
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Paso 3: Crear las 8 familias nuevas
        $families = $this->getFamilyDefinitions();
        $createdFamilyIds = [];

        foreach ($families as $index => $familyDef) {
            $sortOrder = $index + 1;

            $familyId = DB::table('service_families')->insertGetId([
                'contract_id' => $contract->id,
                'name' => $familyDef['name'],
                'code' => $familyDef['code'],
                'description' => $familyDef['description'],
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $createdFamilyIds[$sortOrder] = $familyId;

            // Crear servicio contenedor
            $serviceId = DB::table('services')->insertGetId([
                'service_family_id' => $familyId,
                'name' => $familyDef['service_name'],
                'code' => $familyDef['service_code'],
                'description' => $familyDef['description'],
                'is_active' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Crear subservicios
            foreach ($familyDef['sub_services'] as $ssIndex => $ssDef) {
                $subServiceId = DB::table('sub_services')->insertGetId([
                    'service_id' => $serviceId,
                    'name' => $ssDef['name'],
                    'code' => $ssDef['code'],
                    'description' => $ssDef['description'],
                    'is_active' => true,
                    'cost' => 0,
                    'order' => $ssIndex + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Crear pivote
                DB::table('service_subservices')->insert([
                    'service_family_id' => $familyId,
                    'service_id' => $serviceId,
                    'sub_service_id' => $subServiceId,
                    'name' => $familyDef['name'] . ' - ' . $ssDef['name'],
                    'description' => $ssDef['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Log::info('Migración rebuild cultura: 8 familias creadas exitosamente para contrato 0813-2026.', [
            'family_ids' => $createdFamilyIds,
        ]);
    }

    public function down(): void
    {
        Log::info('Rollback rebuild cultura: Use el backup JSON para restaurar la estructura anterior.');
    }

    private function getFamilyDefinitions(): array
    {
        return [
            // Familia 1
            [
                'name' => 'Publicaciones en Canales Digitales Institucionales',
                'code' => 'CUL_F1_PUBLICACIONES',
                'description' => 'Prestar apoyo en las publicaciones que se requieran, para los distintos canales digitales institucionales, velando por el cumplimiento de los lineamientos estratégicos de comunicación digital definidos por el coordinador del grupo en cuanto a oportunidad, calidad de la información, pertinencia, veracidad, claridad, objetividad y tiempos de entrega.',
                'service_code' => 'CUL_SVC_PUBLICACIONES',
                'service_name' => 'Publicaciones en Canales Digitales',
                'sub_services' => [
                    [
                        'name' => 'Actualización de Contenidos en Portal Principal',
                        'code' => 'CUL_F1_ACT_PORTAL',
                        'description' => 'Cambio de títulos, reemplazo de archivos, edición de textos, actualización de enlaces, imágenes y botones en el portal principal.',
                    ],
                    [
                        'name' => 'Publicación de Noticia o Artículo',
                        'code' => 'CUL_F1_PUB_NOTICIA',
                        'description' => 'Creación y publicación de notas, noticias y artículos institucionales en el portal web.',
                    ],
                    [
                        'name' => 'Publicación de Documento',
                        'code' => 'CUL_F1_PUB_DOCUMENTO',
                        'description' => 'Carga y publicación de documentos (PDF, resoluciones, circulares, informes) en el portal.',
                    ],
                    [
                        'name' => 'Publicación de Banner',
                        'code' => 'CUL_F1_PUB_BANNER',
                        'description' => 'Creación y actualización de banners en el home o secciones internas del portal.',
                    ],
                ],
            ],

            // Familia 2
            [
                'name' => 'Formulación y Ejecución de Estrategia de Comunicación Digital',
                'code' => 'CUL_F2_ESTRATEGIA',
                'description' => 'Prestar apoyo en la formulación y ejecución de la estrategia de comunicación digital, que contemple expectativa, posicionamiento, crecimiento y fidelización de audiencias y comunidades, tanto en las plataformas web como en las redes sociales creadas y administradas por el Ministerio.',
                'service_code' => 'CUL_SVC_ESTRATEGIA',
                'service_name' => 'Estrategia de Comunicación Digital',
                'sub_services' => [
                    [
                        'name' => 'Ejecución de envío de comunicaciones masivas',
                        'code' => 'CUL_F2_COM_MASIVAS',
                        'description' => 'Envío de mailings, boletines y comunicados institucionales a bases de destinatarios a través de plataforma de correo masivo.',
                    ],
                    [
                        'name' => 'Gestión de Secciones Especiales y Campañas',
                        'code' => 'CUL_F2_SECC_CAMPANAS',
                        'description' => 'Desarrollo de landings especiales para posicionamiento, cargue de revistas (Raya), secciones editoriales temáticas y campañas digitales.',
                    ],
                    [
                        'name' => 'Reportes de Analítica Web',
                        'code' => 'CUL_F2_REP_ANALITICA',
                        'description' => 'Análisis de métricas web bajo solicitud, informes de alcance y comportamiento de audiencias.',
                    ],
                    [
                        'name' => 'Gestión de listas de distribución y bases de destinatarios',
                        'code' => 'CUL_F2_GEST_LISTAS',
                        'description' => 'Depuración, segmentación y actualización de bases de correos para envíos masivos.',
                    ],
                ],
            ],

            // Familia 3
            [
                'name' => 'Parrilla de Contenidos, Registro y Estadísticas',
                'code' => 'CUL_F3_PARRILLA',
                'description' => 'Apoyar en el diseño de una parrilla de contenidos para todos los medios digitales internos del Ministerio, en la que se lleve registro diario de las publicaciones que realice, con el fin de generar estadísticas e informes de temáticas, áreas que demandan y formatos utilizados.',
                'service_code' => 'CUL_SVC_PARRILLA',
                'service_name' => 'Registro de Publicaciones y Estadísticas',
                'sub_services' => [
                    [
                        'name' => 'Registro y seguimiento de gestión en sistema',
                        'code' => 'CUL_F3_REG_GESTION',
                        'description' => 'Registro continuo de solicitudes atendidas en el sistema SAPP como bitácora diaria de publicaciones.',
                    ],
                    [
                        'name' => 'Generación de informes y estadísticas de gestión',
                        'code' => 'CUL_F3_INF_ESTADISTICAS',
                        'description' => 'Generación de informes periódicos (trimestral/semestral) con estadísticas de temáticas, áreas demandantes y formatos utilizados.',
                    ],
                ],
            ],

            // Familia 4
            [
                'name' => 'Implementación de Estrategia de Gobierno Digital',
                'code' => 'CUL_F4_GOBIERNO_DIGITAL',
                'description' => 'Acompañar en la implementación de la estrategia de Gobierno Digital de la entidad, en cumplimiento de la normatividad vigente, ajustada a las directrices del Ministerio de Tecnologías de la Información y las Comunicaciones, acorde a los objetivos la estrategia de comunicaciones de la entidad.',
                'service_code' => 'CUL_SVC_GOBIERNO_DIGITAL',
                'service_name' => 'Gobierno Digital y Normatividad',
                'sub_services' => [
                    [
                        'name' => 'Actualización de Sección de Transparencia',
                        'code' => 'CUL_F4_ACT_TRANSPARENCIA',
                        'description' => 'Gestión de contenidos en la sección de transparencia, orden estructural de información según Ley 1712.',
                    ],
                    [
                        'name' => 'Cumplimiento de accesibilidad y lineamientos de Gobierno Digital',
                        'code' => 'CUL_F4_ACCESIBILIDAD',
                        'description' => 'Verificación y aplicación de Res. 1519/2020, Res. 2893/2020, NTC 5854 y WCAG 2.2 en contenidos y desarrollos.',
                    ],
                    [
                        'name' => 'Respuesta a requerimientos ITA y MIPG',
                        'code' => 'CUL_F4_REQ_ITA_MIPG',
                        'description' => 'Atención de solicitudes de los indicadores ITA y MIPG relacionados con presencia web y accesibilidad digital.',
                    ],
                ],
            ],

            // Familia 5
            [
                'name' => 'Cubrimiento de Eventos y Actividades',
                'code' => 'CUL_F5_EVENTOS',
                'description' => 'Apoyar el cubrimiento de eventos y actividades que le sean asignados, dentro y fuera de las instalaciones del Ministerio, y de la ciudad que así lo requiera.',
                'service_code' => 'CUL_SVC_EVENTOS',
                'service_name' => 'Cubrimiento Digital de Eventos',
                'sub_services' => [
                    [
                        'name' => 'Creación de sitios y landings para eventos',
                        'code' => 'CUL_F5_LANDING_EVENTOS',
                        'description' => 'Creación de landings especiales, páginas de inscripción, agendas y calendarios de actividades con producción rápida y urgente.',
                    ],
                    [
                        'name' => 'Actualización y publicación de contenidos de eventos',
                        'code' => 'CUL_F5_CONT_EVENTOS',
                        'description' => 'Publicación de notas, documentos, enlaces de inscripción y archivos de divulgación durante la vigencia del evento o lanzamiento.',
                    ],
                ],
            ],

            // Familia 6
            [
                'name' => 'Reuniones de Estrategia de Comunicación Digital',
                'code' => 'CUL_F6_REUNIONES',
                'description' => 'Asistir a las reuniones que requieran formulación, implementación y/o evaluación de la estrategia de comunicación digital del Ministerio.',
                'service_code' => 'CUL_SVC_REUNIONES',
                'service_name' => 'Reuniones de Estrategia Digital',
                'sub_services' => [
                    [
                        'name' => 'Reuniones de seguimiento con supervisión',
                        'code' => 'CUL_F6_REUNION_SUPER',
                        'description' => 'Reuniones periódicas con supervisor o coordinador para seguimiento de gestión y priorización.',
                    ],
                    [
                        'name' => 'Reuniones de validación y concepto con áreas',
                        'code' => 'CUL_F6_REUNION_VALID',
                        'description' => 'Sesiones con comunicadores, áreas funcionales y TI para validar contenidos, accesibilidad y conceptos editoriales.',
                    ],
                    [
                        'name' => 'Mesas técnicas para renovación de contenidos',
                        'code' => 'CUL_F6_MESA_TECNICA',
                        'description' => 'Sesiones de proyecto para renovación de secciones, nodos y landings con validaciones desde diseño, comunicación y técnica.',
                    ],
                ],
            ],

            // Familia 7
            [
                'name' => 'Confidencialidad de Información Reservada',
                'code' => 'CUL_F7_CONFIDENCIALIDAD',
                'description' => 'Guardar confidencialidad en la información de carácter reservado, que le sea entregada durante la ejecución del contrato.',
                'service_code' => 'CUL_SVC_CONFIDENCIALIDAD',
                'service_name' => 'Gestión de Información Reservada',
                'sub_services' => [
                    [
                        'name' => 'Custodia y gestión de información reservada',
                        'code' => 'CUL_F7_CUSTODIA_INFO',
                        'description' => 'Manejo de bases de destinatarios, credenciales de plataformas e información sensible de áreas.',
                    ],
                    [
                        'name' => 'Informe de cumplimiento de confidencialidad',
                        'code' => 'CUL_F7_INF_CONFIDENCIAL',
                        'description' => 'Generación de reporte describiendo alcance y actividades realizadas para dar cumplimiento a la obligación de confidencialidad.',
                    ],
                ],
            ],

            // Familia 8
            [
                'name' => 'Demás Actividades Asignadas por el Supervisor',
                'code' => 'CUL_F8_DEMAS',
                'description' => 'Las demás que le sean asignadas por parte del supervisor del contrato.',
                'service_code' => 'CUL_SVC_DEMAS',
                'service_name' => 'Actividades Asignadas por Supervisor',
                'sub_services' => [
                    [
                        'name' => 'Correcciones y ajustes de último momento',
                        'code' => 'CUL_F8_CORREC_URGENTES',
                        'description' => 'Correcciones urgentes y ajustes puntuales asignados directamente por el supervisor.',
                    ],
                    [
                        'name' => 'Tareas administrativas e informes',
                        'code' => 'CUL_F8_TAREA_ADMIN',
                        'description' => 'Generación de informes ad-hoc, documentación y coordinación con otras dependencias.',
                    ],
                    [
                        'name' => 'Capacitaciones y sesiones de formación',
                        'code' => 'CUL_F8_CAPACITACIONES',
                        'description' => 'Capacitaciones recibidas de órganos de control, otras dependencias o plataformas tecnológicas.',
                    ],
                    [
                        'name' => 'Asignación de tarea no especificada',
                        'code' => 'CUL_F8_TAREA_NO_ESPEC',
                        'description' => 'Cualquier otra actividad asignada que no encaje en los subservicios anteriores ni en otras familias.',
                    ],
                ],
            ],
        ];
    }
};
