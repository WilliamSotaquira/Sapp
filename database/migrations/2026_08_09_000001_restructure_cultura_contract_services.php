<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reestructura las familias, servicios y subservicios del contrato 0813-2026 (Cultura/MinCultura)
 * para alinearlos con las 8 obligaciones contractuales.
 *
 * IMPORTANTE: Ejecutar `php artisan contract:export-structure 0813-2026` ANTES de esta migración
 * para tener un backup de la estructura anterior.
 *
 * Esta migración:
 * - NO toca el contrato de Movilidad (20251069)
 * - NO elimina subservicios existentes (los desactiva si ya no aplican)
 * - Crea nuevos servicios y subservicios bajo las familias existentes del contrato
 * - Crea las relaciones pivote (service_subservices)
 */
return new class extends Migration
{
    public function up(): void
    {
        $contract = DB::table('contracts')->where('number', '0813-2026')->first();

        if (!$contract) {
            Log::warning('Migración cultura: No se encontró el contrato 0813-2026. Omitiendo.');
            return;
        }

        $contractId = $contract->id;

        // Verificar que el contrato pertenece a Cultura
        $company = DB::table('companies')->where('id', $contract->company_id)->first();
        if (!$company || !str_contains(mb_strtolower($company->name), 'cultura')) {
            Log::warning('Migración cultura: El contrato 0813-2026 no pertenece a una empresa Cultura. Omitiendo.');
            return;
        }

        // Obtener familias existentes del contrato, ordenadas por sort_order
        $families = DB::table('service_families')
            ->where('contract_id', $contractId)
            ->orderBy('sort_order')
            ->get();

        if ($families->isEmpty()) {
            Log::warning('Migración cultura: El contrato 0813-2026 no tiene familias. Omitiendo.');
            return;
        }

        // Definir la nueva estructura: mapa de sort_order => servicios y subservicios
        $structure = $this->getNewStructure();

        foreach ($families as $family) {
            $sortOrder = (int) $family->sort_order;

            if (!isset($structure[$sortOrder])) {
                continue;
            }

            $familyStructure = $structure[$sortOrder];

            // Crear un servicio contenedor para la familia (si no existe)
            $serviceCode = $familyStructure['service_code'];
            $existingService = DB::table('services')
                ->where('service_family_id', $family->id)
                ->where('code', $serviceCode)
                ->first();

            if (!$existingService) {
                $serviceId = DB::table('services')->insertGetId([
                    'service_family_id' => $family->id,
                    'name' => $familyStructure['service_name'],
                    'code' => $serviceCode,
                    'description' => $familyStructure['service_description'],
                    'is_active' => true,
                    'order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $serviceId = $existingService->id;
            }

            // Crear subservicios
            foreach ($familyStructure['sub_services'] as $index => $subServiceData) {
                $existingSub = DB::table('sub_services')
                    ->where('code', $subServiceData['code'])
                    ->first();

                if (!$existingSub) {
                    $subServiceId = DB::table('sub_services')->insertGetId([
                        'service_id' => $serviceId,
                        'name' => $subServiceData['name'],
                        'code' => $subServiceData['code'],
                        'description' => $subServiceData['description'],
                        'is_active' => true,
                        'cost' => 0,
                        'order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $subServiceId = $existingSub->id;
                    // Asegurar que está activo y vinculado al servicio correcto
                    DB::table('sub_services')
                        ->where('id', $subServiceId)
                        ->update([
                            'service_id' => $serviceId,
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                }

                // Crear pivote service_subservices si no existe
                $existingPivot = DB::table('service_subservices')
                    ->where('service_family_id', $family->id)
                    ->where('service_id', $serviceId)
                    ->where('sub_service_id', $subServiceId)
                    ->first();

                if (!$existingPivot) {
                    DB::table('service_subservices')->insert([
                        'service_family_id' => $family->id,
                        'service_id' => $serviceId,
                        'sub_service_id' => $subServiceId,
                        'name' => $family->name . ' - ' . $subServiceData['name'],
                        'description' => $subServiceData['description'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Log::info('Migración cultura: Estructura del contrato 0813-2026 actualizada exitosamente.');
    }

    public function down(): void
    {
        // El rollback no elimina datos — usar el backup JSON para restaurar manualmente.
        Log::info('Rollback de migración cultura: Use el backup JSON generado con contract:export-structure para restaurar.');
    }

    private function getNewStructure(): array
    {
        return [
            // Familia 1: Publicaciones en canales digitales institucionales
            1 => [
                'service_code' => 'CUL_PUB_CANALES',
                'service_name' => 'Publicaciones en Canales Digitales',
                'service_description' => 'Publicaciones para los distintos canales digitales institucionales del Ministerio.',
                'sub_services' => [
                    [
                        'name' => 'Actualización de Contenidos en Portal Principal',
                        'code' => 'CUL_ACT_PORTAL',
                        'description' => 'Cambio de títulos, reemplazo de archivos, edición de textos, actualización de enlaces, imágenes y botones en el portal principal.',
                    ],
                    [
                        'name' => 'Publicación de Noticia o Artículo',
                        'code' => 'CUL_PUB_NOTICIA',
                        'description' => 'Creación y publicación de notas, noticias y artículos institucionales en el portal web.',
                    ],
                    [
                        'name' => 'Publicación de Documento',
                        'code' => 'CUL_PUB_DOCUMENTO',
                        'description' => 'Carga y publicación de documentos (PDF, resoluciones, circulares, informes) en el portal.',
                    ],
                    [
                        'name' => 'Publicación de Banner',
                        'code' => 'CUL_PUB_BANNER',
                        'description' => 'Creación y actualización de banners en el home o secciones internas del portal.',
                    ],
                ],
            ],

            // Familia 2: Formulación y ejecución de estrategia de comunicación digital
            2 => [
                'service_code' => 'CUL_ESTRATEGIA_DIGITAL',
                'service_name' => 'Estrategia de Comunicación Digital',
                'service_description' => 'Formulación y ejecución de la estrategia de comunicación digital para posicionamiento y fidelización de audiencias.',
                'sub_services' => [
                    [
                        'name' => 'Ejecución de envío de comunicaciones masivas',
                        'code' => 'CUL_COM_MASIVAS',
                        'description' => 'Envío de mailings, boletines y comunicados institucionales a bases de destinatarios a través de plataforma de correo masivo.',
                    ],
                    [
                        'name' => 'Gestión de Secciones Especiales y Campañas',
                        'code' => 'CUL_SECC_CAMPANAS',
                        'description' => 'Desarrollo de landings especiales para posicionamiento, cargue de revistas (Raya), secciones editoriales temáticas y campañas digitales.',
                    ],
                    [
                        'name' => 'Reportes de Analítica Web',
                        'code' => 'CUL_REP_ANALITICA',
                        'description' => 'Análisis de métricas web bajo solicitud, informes de alcance y comportamiento de audiencias.',
                    ],
                    [
                        'name' => 'Gestión de listas de distribución y bases de destinatarios',
                        'code' => 'CUL_GEST_LISTAS',
                        'description' => 'Depuración, segmentación y actualización de bases de correos para envíos masivos.',
                    ],
                ],
            ],

            // Familia 3: Parrilla de contenidos, registro y estadísticas
            3 => [
                'service_code' => 'CUL_PARRILLA_REG',
                'service_name' => 'Registro de Publicaciones y Estadísticas',
                'service_description' => 'Registro diario de publicaciones y generación de estadísticas e informes de gestión.',
                'sub_services' => [
                    [
                        'name' => 'Registro y seguimiento de gestión en sistema',
                        'code' => 'CUL_REG_GESTION',
                        'description' => 'Registro continuo de solicitudes atendidas en el sistema SAPP como bitácora diaria de publicaciones.',
                    ],
                    [
                        'name' => 'Generación de informes y estadísticas de gestión',
                        'code' => 'CUL_INF_ESTADISTICAS',
                        'description' => 'Generación de informes periódicos (trimestral/semestral) con estadísticas de temáticas, áreas demandantes y formatos utilizados.',
                    ],
                ],
            ],

            // Familia 4: Implementación de estrategia de Gobierno Digital
            4 => [
                'service_code' => 'CUL_GOBIERNO_DIGITAL',
                'service_name' => 'Gobierno Digital y Normatividad',
                'service_description' => 'Implementación de la estrategia de Gobierno Digital en cumplimiento de la normatividad vigente (MinTIC).',
                'sub_services' => [
                    [
                        'name' => 'Actualización de Sección de Transparencia',
                        'code' => 'CUL_ACT_TRANSPARENCIA',
                        'description' => 'Gestión de contenidos en la sección de transparencia, orden estructural de información según Ley 1712.',
                    ],
                    [
                        'name' => 'Cumplimiento de accesibilidad y lineamientos de Gobierno Digital',
                        'code' => 'CUL_ACCESIBILIDAD',
                        'description' => 'Verificación y aplicación de Res. 1519/2020, Res. 2893/2020, NTC 5854 y WCAG 2.2 en contenidos y desarrollos.',
                    ],
                    [
                        'name' => 'Respuesta a requerimientos ITA y MIPG',
                        'code' => 'CUL_REQ_ITA_MIPG',
                        'description' => 'Atención de solicitudes de los indicadores ITA y MIPG relacionados con presencia web y accesibilidad digital.',
                    ],
                ],
            ],

            // Familia 5: Cubrimiento de eventos y actividades
            5 => [
                'service_code' => 'CUL_EVENTOS',
                'service_name' => 'Cubrimiento de Eventos y Actividades',
                'service_description' => 'Apoyo al cubrimiento digital de eventos y actividades del Ministerio.',
                'sub_services' => [
                    [
                        'name' => 'Creación de sitios y landings para eventos',
                        'code' => 'CUL_LANDING_EVENTOS',
                        'description' => 'Creación de landings especiales, páginas de inscripción, agendas y calendarios de actividades con producción rápida y urgente.',
                    ],
                    [
                        'name' => 'Actualización y publicación de contenidos de eventos',
                        'code' => 'CUL_CONT_EVENTOS',
                        'description' => 'Publicación de notas, documentos, enlaces de inscripción y archivos de divulgación durante la vigencia del evento o lanzamiento.',
                    ],
                ],
            ],

            // Familia 6: Reuniones de estrategia de comunicación digital
            6 => [
                'service_code' => 'CUL_REUNIONES',
                'service_name' => 'Reuniones de Estrategia Digital',
                'service_description' => 'Asistencia a reuniones de formulación, implementación y evaluación de la estrategia de comunicación digital.',
                'sub_services' => [
                    [
                        'name' => 'Reuniones de seguimiento con supervisión',
                        'code' => 'CUL_REUNION_SUPER',
                        'description' => 'Reuniones periódicas con supervisor o coordinador para seguimiento de gestión y priorización.',
                    ],
                    [
                        'name' => 'Reuniones de validación y concepto con áreas',
                        'code' => 'CUL_REUNION_VALID',
                        'description' => 'Sesiones con comunicadores, áreas funcionales y TI para validar contenidos, accesibilidad y conceptos editoriales.',
                    ],
                    [
                        'name' => 'Mesas técnicas para renovación de contenidos',
                        'code' => 'CUL_MESA_TECNICA',
                        'description' => 'Sesiones de proyecto para renovación de secciones, nodos y landings con validaciones desde diseño, comunicación y técnica.',
                    ],
                ],
            ],

            // Familia 7: Confidencialidad de información reservada
            7 => [
                'service_code' => 'CUL_CONFIDENCIALIDAD',
                'service_name' => 'Confidencialidad de Información',
                'service_description' => 'Guardar confidencialidad en la información de carácter reservado entregada durante la ejecución del contrato.',
                'sub_services' => [
                    [
                        'name' => 'Custodia y gestión de información reservada',
                        'code' => 'CUL_CUSTODIA_INFO',
                        'description' => 'Manejo de bases de destinatarios, credenciales de plataformas e información sensible de áreas.',
                    ],
                    [
                        'name' => 'Informe de cumplimiento de confidencialidad',
                        'code' => 'CUL_INF_CONFIDENCIAL',
                        'description' => 'Generación de reporte describiendo alcance y actividades realizadas para dar cumplimiento a la obligación de confidencialidad.',
                    ],
                ],
            ],

            // Familia 8: Demás actividades asignadas por el supervisor
            8 => [
                'service_code' => 'CUL_DEMAS_ASIGNADAS',
                'service_name' => 'Actividades Asignadas por Supervisor',
                'service_description' => 'Las demás actividades asignadas por el supervisor del contrato.',
                'sub_services' => [
                    [
                        'name' => 'Correcciones y ajustes de último momento',
                        'code' => 'CUL_CORREC_URGENTES',
                        'description' => 'Correcciones urgentes y ajustes puntuales asignados directamente por el supervisor.',
                    ],
                    [
                        'name' => 'Tareas administrativas e informes',
                        'code' => 'CUL_TAREA_ADMIN',
                        'description' => 'Generación de informes ad-hoc, documentación y coordinación con otras dependencias.',
                    ],
                    [
                        'name' => 'Capacitaciones y sesiones de formación',
                        'code' => 'CUL_CAPACITACIONES',
                        'description' => 'Capacitaciones recibidas de órganos de control, otras dependencias o plataformas tecnológicas.',
                    ],
                    [
                        'name' => 'Asignación de tarea no especificada',
                        'code' => 'CUL_TAREA_NO_ESPEC',
                        'description' => 'Cualquier otra actividad asignada que no encaje en los subservicios anteriores ni en otras familias.',
                    ],
                ],
            ],
        ];
    }
};
