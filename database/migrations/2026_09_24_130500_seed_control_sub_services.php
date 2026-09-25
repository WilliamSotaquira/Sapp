<?php

use App\Models\SubService;
use Illuminate\Database\Migrations\Migration;

/**
 * Marca (idempotente) el subservicio de control por contrato.
 *
 * Se resuelve por nombre dentro de cada contrato para no depender de IDs fijos
 * (que cambian si se reconstruye el catálogo). Preferencia de nombres, en orden:
 *   1) "Verificación de Publicación"
 *   2) "Validación Previa a Publicación"
 *
 * Si un contrato no tiene ninguno de esos subservicios, simplemente no se marca:
 * el líder podrá configurarlo manualmente más adelante.
 */
return new class extends Migration
{
    public function up(): void
    {
        $preferredNames = [
            'Verificación de Publicación',
            'Validación Previa a Publicación',
        ];

        $contractIds = \App\Models\ServiceFamily::query()
            ->distinct()
            ->pluck('contract_id')
            ->filter();

        foreach ($contractIds as $contractId) {
            // Si ya hay uno marcado en este contrato, respetarlo.
            $already = SubService::query()
                ->where('is_control', true)
                ->whereHas('service.family', fn ($q) => $q->where('contract_id', $contractId))
                ->exists();

            if ($already) {
                continue;
            }

            foreach ($preferredNames as $name) {
                $sub = SubService::query()
                    ->where('name', $name)
                    ->whereHas('service.family', fn ($q) => $q->where('contract_id', $contractId))
                    ->first();

                if ($sub) {
                    $sub->is_control = true;
                    $sub->save();
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        // Reversible: desmarcar todos los subservicios de control.
        SubService::query()->where('is_control', true)->update(['is_control' => false]);
    }
};
