<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequestTypeSeeder extends Seeder
{
    /**
     * Seed the request_types table with default types.
     */
    public function run(): void
    {
        $types = [
            [
                'slug' => 'general',
                'name' => 'General',
                'description' => 'Solicitud de servicio estándar sin flujo especializado',
                'is_active' => true,
            ],
            [
                'slug' => 'reunion',
                'name' => 'Reunión',
                'description' => 'Solicitud para gestionar el ciclo de vida de reuniones con participantes, evidencia y compromisos',
                'is_active' => true,
            ],
            [
                'slug' => 'compromiso',
                'name' => 'Compromiso',
                'description' => 'Solicitud derivada de un compromiso adquirido en reunión u otro proceso',
                'is_active' => true,
            ],
            [
                'slug' => 'seguimiento',
                'name' => 'Seguimiento',
                'description' => 'Solicitud de seguimiento a un proceso, actividad o compromiso previo',
                'is_active' => true,
            ],
            [
                'slug' => 'solicitud_documental',
                'name' => 'Solicitud Documental',
                'description' => 'Solicitud para gestión de documentos, actas o soportes formales',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            DB::table('request_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'is_active' => $type['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Tipos de solicitud sembrados exitosamente!');
        $this->command->info('Total: ' . count($types) . ' tipos creados/actualizados.');
    }
}
