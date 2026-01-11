<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use Illuminate\Support\Facades\DB;

class ServiceSubserviceSeeder extends Seeder
{
    public function run()
    {
        // Este seeder construye la tabla pivote service_subservices enlazando:
        // service_family_id + service_id + sub_service_id
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('service_subservices')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $subServices = SubService::query()->with('service')->get();

        if ($subServices->isEmpty()) {
            $this->command->warn('No hay SubServices. Ejecuta SubServiceSeeder primero.');
            return;
        }

        foreach ($subServices as $subService) {
            if (!$subService->service) {
                $this->command->warn("SubService sin service asociado: {$subService->id}");
                continue;
            }

            ServiceSubservice::query()->create([
                'service_family_id' => $subService->service->service_family_id,
                'service_id' => $subService->service_id,
                'sub_service_id' => $subService->id,
                'name' => $subService->name,
                'description' => $subService->description,
                'is_active' => (bool) ($subService->is_active ?? true),
            ]);
        }

        $this->command->info('ServiceSubserviceSeeder: pivote service_subservices creado exitosamente.');
    }
}
