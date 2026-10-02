<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Exporta el catalogo completo de servicios (Familia > Servicio > Subservicio)
 * junto con sus tiempos de SLA, pensado para comparar contra un gestor GLPI.
 *
 * Genera dos archivos:
 *  - Un Markdown legible con la jerarquia y una tabla de SLAs por subservicio.
 *  - Un CSV plano (una fila por SLA) apto para importar/comparar en hoja de calculo.
 */
class ExportServiceCatalog extends Command
{
    protected $signature = 'catalog:export
                            {--contract= : Numero de contrato para filtrar (ej: 0813-2026). Si se omite, exporta todo.}
                            {--only-active : Incluir solo familias/servicios/subservicios/SLAs activos}
                            {--glpi : Generar ademas CSVs con formato de importacion para GLPI (Data Injection)}
                            {--output=docs/catalog : Directorio de salida relativo a la raiz del proyecto}';

    protected $description = 'Exporta el catalogo de familias, servicios, subservicios y SLAs (Markdown + CSV) para comparar/importar en GLPI';

    public function handle(): int
    {
        $onlyActive = (bool) $this->option('only-active');
        $contractNumber = $this->option('contract') ? trim((string) $this->option('contract')) : null;

        $contract = null;
        if ($contractNumber) {
            $contract = Contract::where('number', $contractNumber)->first();
            if (!$contract) {
                $this->error("No se encontro el contrato con numero: {$contractNumber}");
                return self::FAILURE;
            }
        }

        // Cargar familias con toda la jerarquia anidada.
        $familyQuery = ServiceFamily::query()
            ->with([
                'services' => function ($q) use ($onlyActive) {
                    if ($onlyActive) {
                        $q->where('is_active', true);
                    }
                    $q->orderBy('order')->orderBy('name');
                },
                'services.subServices' => function ($q) use ($onlyActive) {
                    if ($onlyActive) {
                        $q->where('is_active', true);
                    }
                    $q->orderBy('order')->orderBy('name');
                },
                'contract:id,number,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($contract) {
            $familyQuery->where('contract_id', $contract->id);
        }
        if ($onlyActive) {
            $familyQuery->where('is_active', true);
        }

        $families = $familyQuery->get();

        if ($families->isEmpty()) {
            $this->warn('No se encontraron familias con los filtros indicados.');
            return self::SUCCESS;
        }

        // Precargar SLAs indexados por service_subservice_id y, como respaldo, la
        // tabla puente para mapear cada subservicio con sus SLAs.
        $slaQuery = ServiceLevelAgreement::query()
            ->with('serviceSubservice:id,service_family_id,service_id,sub_service_id');
        if ($onlyActive) {
            $slaQuery->where('is_active', true);
        }
        $allSlas = $slaQuery->get();

        // Mapa: sub_service_id => coleccion de SLAs
        $slasBySubService = [];
        foreach ($allSlas as $sla) {
            $subId = $sla->serviceSubservice->sub_service_id ?? null;
            if ($subId === null) {
                continue;
            }
            $slasBySubService[$subId][] = $sla;
        }

        // Orden de criticidad para presentacion consistente.
        $critOrder = ['CRITICA' => 0, 'ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];

        $md = [];
        $csvRows = [];
        $csvHeader = [
            'Familia', 'Codigo Familia', 'Contrato',
            'Servicio', 'Codigo Servicio',
            'Subservicio', 'Codigo Subservicio',
            'Criticidad',
            'Aceptacion (min)', 'Respuesta (min)', 'Resolucion (min)',
            'Respuesta (h)', 'Resolucion (h)',
            'Disponibilidad (%)', 'Activo',
        ];

        $summary = [
            'families' => 0, 'services' => 0, 'sub_services' => 0, 'slas' => 0,
        ];

        // --- Acumuladores para el formato GLPI (solo se llenan si --glpi) ---
        $glpi = (bool) $this->option('glpi');
        // Categorias ITIL: una fila por nodo (familia, servicio, subservicio) con completename jerarquico.
        $glpiCategories = [];       // clave completename => fila
        // SLAs: una fila por (subservicio x criticidad x tipo TTO/TTR).
        $glpiSlaRows = [];
        // Deduplicar categorias por su ruta completa.
        $seenCategoryPaths = [];

        $md[] = '# Catalogo de servicios y SLAs';
        $md[] = '';
        $md[] = 'Generado: ' . now()->format('Y-m-d H:i');
        if ($contract) {
            $md[] = 'Contrato: ' . $contract->number . ($contract->name ? ' - ' . $contract->name : '');
        } else {
            $md[] = 'Alcance: todos los contratos/familias';
        }
        $md[] = 'Filtro solo activos: ' . ($onlyActive ? 'Si' : 'No');
        $md[] = '';
        $md[] = '> Nota: los tiempos se muestran tal como estan en la base de datos. Existen columnas en minutos (canonicas) y en horas (agregadas despues); ambas se incluyen para facilitar el mapeo con GLPI.';
        $md[] = '';

        foreach ($families as $family) {
            $summary['families']++;
            $contractLabel = $family->contract?->number ?? '-';

            $md[] = '## Familia: ' . $family->name . ' (' . ($family->code ?: 's/codigo') . ')';
            $md[] = '';
            $md[] = '- Contrato: ' . $contractLabel;
            if ($family->description) {
                $md[] = '- Descripcion: ' . $family->description;
            }
            $md[] = '- Estado: ' . ($family->is_active ? 'Activa' : 'Inactiva');
            $md[] = '';

            if ($glpi) {
                $famPath = $family->name;
                if (!isset($seenCategoryPaths[$famPath])) {
                    $seenCategoryPaths[$famPath] = true;
                    $glpiCategories[] = [
                        'name' => $family->name,
                        'completename' => $famPath,
                        'comment' => (string) $family->description,
                        'is_helpdeskvisible' => $family->is_active ? '1' : '0',
                        'level' => 1,
                    ];
                }
            }

            if ($family->services->isEmpty()) {
                $md[] = '_Sin servicios._';
                $md[] = '';
                continue;
            }

            foreach ($family->services as $service) {
                $summary['services']++;
                $md[] = '### Servicio: ' . $service->name . ' (' . ($service->code ?: 's/codigo') . ')';
                if ($service->description) {
                    $md[] = '';
                    $md[] = $service->description;
                }
                $md[] = '';

                if ($glpi) {
                    $svcPath = $family->name . ' > ' . $service->name;
                    if (!isset($seenCategoryPaths[$svcPath])) {
                        $seenCategoryPaths[$svcPath] = true;
                        $glpiCategories[] = [
                            'name' => $service->name,
                            'completename' => $svcPath,
                            'comment' => (string) $service->description,
                            'is_helpdeskvisible' => $service->is_active ? '1' : '0',
                            'level' => 2,
                        ];
                    }
                }

                if ($service->subServices->isEmpty()) {
                    $md[] = '_Sin subservicios._';
                    $md[] = '';
                    continue;
                }

                foreach ($service->subServices as $sub) {
                    $summary['sub_services']++;
                    $md[] = '#### Subservicio: ' . $sub->name . ' (' . ($sub->code ?: 's/codigo') . ')';
                    if ($sub->description) {
                        $md[] = '';
                        $md[] = $sub->description;
                    }
                    $md[] = '';

                    $subPath = $family->name . ' > ' . $service->name . ' > ' . $sub->name;
                    if ($glpi && !isset($seenCategoryPaths[$subPath])) {
                        $seenCategoryPaths[$subPath] = true;
                        $glpiCategories[] = [
                            'name' => $sub->name,
                            'completename' => $subPath,
                            'comment' => (string) $sub->description,
                            'is_helpdeskvisible' => $sub->is_active ? '1' : '0',
                            'level' => 3,
                        ];
                    }

                    $slas = collect($slasBySubService[$sub->id] ?? [])
                        // Deduplicar SLAs redundantes: un mismo subservicio puede tener
                        // varias filas en la tabla puente que arrastran SLAs con identica
                        // criticidad y tiempos. Nos quedamos con uno por combinacion.
                        ->unique(fn ($s) => implode('|', [
                            $s->criticality_level,
                            $s->acceptance_time_minutes,
                            $s->response_time_minutes,
                            $s->resolution_time_minutes,
                        ]))
                        ->sortBy(fn ($s) => $critOrder[$s->criticality_level] ?? 99)
                        ->values();

                    if ($slas->isEmpty()) {
                        $md[] = '_Sin SLA definido._';
                        $md[] = '';

                        // Fila CSV sin SLA para no perder el subservicio en la comparacion.
                        $csvRows[] = [
                            $family->name, $family->code, $contractLabel,
                            $service->name, $service->code,
                            $sub->name, $sub->code,
                            '', '', '', '', '', '', '', $sub->is_active ? 'Si' : 'No',
                        ];
                        continue;
                    }

                    $md[] = '| Criticidad | Aceptacion (min) | Respuesta (min) | Resolucion (min) | Respuesta (h) | Resolucion (h) | Disponibilidad (%) |';
                    $md[] = '|---|---|---|---|---|---|---|';

                    foreach ($slas as $sla) {
                        $summary['slas']++;
                        $md[] = '| ' . implode(' | ', [
                            $sla->criticality_level ?: '-',
                            $sla->acceptance_time_minutes ?? '-',
                            $sla->response_time_minutes ?? '-',
                            $sla->resolution_time_minutes ?? '-',
                            $sla->response_time_hours ?? '-',
                            $sla->resolution_time_hours ?? '-',
                            $sla->availability_percentage ?? '-',
                        ]) . ' |';

                        $csvRows[] = [
                            $family->name, $family->code, $contractLabel,
                            $service->name, $service->code,
                            $sub->name, $sub->code,
                            $sla->criticality_level,
                            $sla->acceptance_time_minutes,
                            $sla->response_time_minutes,
                            $sla->resolution_time_minutes,
                            $sla->response_time_hours,
                            $sla->resolution_time_hours,
                            $sla->availability_percentage,
                            $sla->is_active ? 'Si' : 'No',
                        ];

                        if ($glpi) {
                            // GLPI define cada SLA por tipo: TTO (Time To Own = respuesta)
                            // y TTR (Time To Resolve = resolucion). Cada uno es una fila
                            // con valor + unidad. Usamos los minutos como valor canonico.
                            //
                            // GLPI exige nombres de SLA unicos. Como hay subservicios
                            // homonimos en distintas familias/servicios, anteponemos un
                            // prefijo con la rama para evitar colisiones de nombre.
                            $baseName = $family->name . ' / ' . $service->name . ' / '
                                . $sub->name . ' - ' . $sla->criticality_level;
                            $crit = $sla->criticality_level;

                            if (!is_null($sla->response_time_minutes)) {
                                $glpiSlaRows[] = [
                                    'name' => $baseName . ' - TTO',
                                    'type' => 'Time to own',       // GTA / tiempo de respuesta
                                    'number_time' => $sla->response_time_minutes,
                                    'definition_time' => 'minute',
                                    'category_completename' => $subPath,
                                    'criticality' => $crit,
                                    'calendar' => 'Default',
                                ];
                            }
                            if (!is_null($sla->resolution_time_minutes)) {
                                $glpiSlaRows[] = [
                                    'name' => $baseName . ' - TTR',
                                    'type' => 'Time to resolve',   // GTR / tiempo de resolucion
                                    'number_time' => $sla->resolution_time_minutes,
                                    'definition_time' => 'minute',
                                    'category_completename' => $subPath,
                                    'criticality' => $crit,
                                    'calendar' => 'Default',
                                ];
                            }
                        }
                    }
                    $md[] = '';
                }
            }
        }

        // Resumen final en el Markdown.
        $md[] = '---';
        $md[] = '';
        $md[] = '## Resumen';
        $md[] = '';
        $md[] = '- Familias: ' . $summary['families'];
        $md[] = '- Servicios: ' . $summary['services'];
        $md[] = '- Subservicios: ' . $summary['sub_services'];
        $md[] = '- SLAs: ' . $summary['slas'];
        $md[] = '';

        // Guardar archivos.
        $outputDir = base_path($this->option('output'));
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $slug = $contract ? str_replace(['/', '\\', ' '], '-', $contract->number) : 'todos';
        $stamp = now()->format('Ymd_His');

        $mdPath = $outputDir . "/catalogo_servicios_{$slug}_{$stamp}.md";
        $csvPath = $outputDir . "/catalogo_servicios_{$slug}_{$stamp}.csv";

        File::put($mdPath, implode(PHP_EOL, $md));
        $this->writeCsv($csvPath, $csvHeader, $csvRows);

        $this->info('Catalogo exportado:');
        $this->line('  Markdown: ' . $mdPath);
        $this->line('  CSV:      ' . $csvPath);

        // --- Salida especifica para GLPI ---
        $glpiCatPath = null;
        $glpiSlaPath = null;
        if ($glpi) {
            $glpiCatPath = $outputDir . "/glpi_categorias_itil_{$slug}_{$stamp}.csv";
            $glpiSlaPath = $outputDir . "/glpi_slas_{$slug}_{$stamp}.csv";

            // Ordenar categorias por nivel para que los padres se creen antes que los hijos.
            usort($glpiCategories, fn ($a, $b) => $a['level'] <=> $b['level']);

            // Salvaguarda: eliminar filas SLA totalmente redundantes (mismo nombre y tipo).
            $seenSla = [];
            $glpiSlaRows = array_values(array_filter($glpiSlaRows, function ($row) use (&$seenSla) {
                $key = $row['name'] . '||' . $row['type'];
                if (isset($seenSla[$key])) {
                    return false;
                }
                $seenSla[$key] = true;
                return true;
            }));

            $this->writeCsv(
                $glpiCatPath,
                ['name', 'completename', 'comment', 'is_helpdeskvisible'],
                array_map(fn ($c) => [
                    $c['name'], $c['completename'], $c['comment'], $c['is_helpdeskvisible'],
                ], $glpiCategories)
            );

            $this->writeCsv(
                $glpiSlaPath,
                ['name', 'type', 'number_time', 'definition_time', 'calendar', 'itilcategory_completename', 'criticality'],
                array_map(fn ($s) => [
                    $s['name'], $s['type'], $s['number_time'], $s['definition_time'],
                    $s['calendar'], $s['category_completename'], $s['criticality'],
                ], $glpiSlaRows)
            );

            $this->line('  GLPI categorias: ' . $glpiCatPath);
            $this->line('  GLPI SLAs:       ' . $glpiSlaPath);
        }

        $this->newLine();
        $metrics = [
            ['Familias', $summary['families']],
            ['Servicios', $summary['services']],
            ['Subservicios', $summary['sub_services']],
            ['SLAs', $summary['slas']],
            ['Filas CSV comparativo', count($csvRows)],
        ];
        if ($glpi) {
            $metrics[] = ['GLPI categorias ITIL', count($glpiCategories)];
            $metrics[] = ['GLPI filas SLA (TTO+TTR)', count($glpiSlaRows)];
        }
        $this->table(['Metrica', 'Valor'], $metrics);

        return self::SUCCESS;
    }

    /**
     * Escribe un CSV con BOM UTF-8 (para que Excel muestre bien los acentos)
     * y separador coma, que es el que espera el plugin Data Injection de GLPI.
     *
     * @param  array<int, string>  $header
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function writeCsv(string $path, array $header, array $rows): void
    {
        $content = "\xEF\xBB\xBF";
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $header);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $content .= stream_get_contents($fh);
        fclose($fh);
        File::put($path, $content);
    }
}
