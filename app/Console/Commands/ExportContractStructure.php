<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ServiceFamily;
use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceSubservice;
use App\Models\ServiceLevelAgreement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportContractStructure extends Command
{
    protected $signature = 'contract:export-structure
                            {contractNumber : Numero de contrato (ej: 0813-2026)}
                            {--output=docs/backup : Directorio de salida relativo a la raiz del proyecto}';

    protected $description = 'Exporta la estructura completa de un contrato (familias, servicios, subservicios, SLAs) a un archivo JSON como backup';

    public function handle(): int
    {
        $contractNumber = trim((string) $this->argument('contractNumber'));

        $contract = Contract::query()
            ->with('company:id,name')
            ->where('number', $contractNumber)
            ->first();

        if (!$contract) {
            $this->error("No se encontro el contrato con numero: {$contractNumber}");
            return self::FAILURE;
        }

        $this->info("Exportando estructura del contrato {$contractNumber} (Empresa: {$contract->company->name})...");

        // Obtener familias del contrato
        $families = ServiceFamily::where('contract_id', $contract->id)
            ->orderBy('sort_order')
            ->get();

        if ($families->isEmpty()) {
            $this->warn("El contrato no tiene familias de servicio asociadas.");
            return self::SUCCESS;
        }

        $export = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'contract_number' => $contract->number,
                'contract_name' => $contract->name,
                'contract_id' => $contract->id,
                'company_id' => $contract->company_id,
                'company_name' => $contract->company->name,
                'is_active' => $contract->is_active,
            ],
            'families' => [],
            'summary' => [
                'total_families' => 0,
                'total_services' => 0,
                'total_sub_services' => 0,
                'total_service_subservices' => 0,
                'total_slas' => 0,
            ],
        ];

        foreach ($families as $family) {
            $familyData = [
                'id' => $family->id,
                'name' => $family->name,
                'code' => $family->code,
                'description' => $family->description,
                'is_active' => $family->is_active,
                'sort_order' => $family->sort_order,
                'services' => [],
                'slas' => [],
            ];

            // Servicios de esta familia
            $services = Service::where('service_family_id', $family->id)
                ->orderBy('order')
                ->get();

            foreach ($services as $service) {
                $serviceData = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'description' => $service->description,
                    'is_active' => $service->is_active,
                    'order' => $service->order,
                    'sub_services' => [],
                ];

                // Subservicios de este servicio
                $subServices = SubService::where('service_id', $service->id)
                    ->orderBy('order')
                    ->get();

                foreach ($subServices as $subService) {
                    $serviceData['sub_services'][] = [
                        'id' => $subService->id,
                        'name' => $subService->name,
                        'code' => $subService->code,
                        'description' => $subService->description,
                        'is_active' => $subService->is_active,
                        'cost' => $subService->cost,
                        'order' => $subService->order,
                    ];
                    $export['summary']['total_sub_services']++;
                }

                $familyData['services'][] = $serviceData;
                $export['summary']['total_services']++;
            }

            // Service_subservices (pivote) de esta familia
            $serviceSubservices = ServiceSubservice::where('service_family_id', $family->id)
                ->get();

            $familyData['service_subservices'] = $serviceSubservices->map(function ($ss) {
                return [
                    'id' => $ss->id,
                    'service_family_id' => $ss->service_family_id,
                    'service_id' => $ss->service_id,
                    'sub_service_id' => $ss->sub_service_id,
                    'name' => $ss->name,
                    'description' => $ss->description,
                    'is_active' => $ss->is_active,
                ];
            })->values()->toArray();
            $export['summary']['total_service_subservices'] += count($familyData['service_subservices']);

            // SLAs de esta familia
            $slas = ServiceLevelAgreement::where('service_family_id', $family->id)
                ->get();

            foreach ($slas as $sla) {
                $familyData['slas'][] = [
                    'id' => $sla->id,
                    'name' => $sla->name,
                    'service_subservice_id' => $sla->service_subservice_id,
                    'sub_service_id' => $sla->sub_service_id,
                    'criticality_level' => $sla->criticality_level,
                    'acceptance_time_minutes' => $sla->acceptance_time_minutes,
                    'response_time_minutes' => $sla->response_time_minutes,
                    'resolution_time_minutes' => $sla->resolution_time_minutes,
                    'is_active' => $sla->is_active,
                ];
                $export['summary']['total_slas']++;
            }

            $export['families'][] = $familyData;
            $export['summary']['total_families']++;
        }

        // Guardar archivo
        $outputDir = base_path($this->option('output'));
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $filename = "contract_{$contractNumber}_backup_" . now()->format('Ymd_His') . '.json';
        $filepath = $outputDir . '/' . $filename;

        File::put($filepath, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Backup guardado en: {$filepath}");
        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Familias', $export['summary']['total_families']],
                ['Servicios', $export['summary']['total_services']],
                ['Subservicios', $export['summary']['total_sub_services']],
                ['Pivotes (service_subservices)', $export['summary']['total_service_subservices']],
                ['SLAs', $export['summary']['total_slas']],
            ]
        );

        return self::SUCCESS;
    }
}
