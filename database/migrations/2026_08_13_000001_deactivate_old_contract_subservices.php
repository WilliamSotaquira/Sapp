<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Desactivar subservicios del contrato antiguo (contract_id=1) que tienen
     * duplicados activos en el contrato nuevo (contract_id=2).
     *
     * Esto previene que el clasificador seleccione subservicios del contrato
     * incorrecto cuando existen nombres iguales en ambos contratos.
     */
    public function up(): void
    {
        // Obtener subservicios activos del contrato viejo (1) que tienen nombre duplicado en contrato nuevo (2)
        $oldContractSubServices = DB::table('sub_services as ss')
            ->join('services as s', 's.id', '=', 'ss.service_id')
            ->join('service_families as sf', 'sf.id', '=', 's.service_family_id')
            ->where('sf.contract_id', 1)
            ->where('ss.is_active', 1)
            ->pluck('ss.name', 'ss.id');

        $newContractSubServices = DB::table('sub_services as ss')
            ->join('services as s', 's.id', '=', 'ss.service_id')
            ->join('service_families as sf', 'sf.id', '=', 's.service_family_id')
            ->where('sf.contract_id', 2)
            ->where('ss.is_active', 1)
            ->pluck('ss.name');

        $toDeactivate = [];
        foreach ($oldContractSubServices as $id => $name) {
            if ($newContractSubServices->contains($name)) {
                $toDeactivate[] = $id;
            }
        }

        if (!empty($toDeactivate)) {
            DB::table('sub_services')
                ->whereIn('id', $toDeactivate)
                ->update(['is_active' => false]);
        }

        // Adicionalmente, desactivar TODOS los subservicios del contrato viejo
        // ya que el contrato activo es el 2 y no deberían ser seleccionables
        $allOldIds = DB::table('sub_services as ss')
            ->join('services as s', 's.id', '=', 'ss.service_id')
            ->join('service_families as sf', 'sf.id', '=', 's.service_family_id')
            ->where('sf.contract_id', 1)
            ->where('ss.is_active', 1)
            ->pluck('ss.id');

        if ($allOldIds->isNotEmpty()) {
            DB::table('sub_services')
                ->whereIn('id', $allOldIds->toArray())
                ->update(['is_active' => false]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reactivar todos los subservicios del contrato 1
        $oldIds = DB::table('sub_services as ss')
            ->join('services as s', 's.id', '=', 'ss.service_id')
            ->join('service_families as sf', 'sf.id', '=', 's.service_family_id')
            ->where('sf.contract_id', 1)
            ->pluck('ss.id');

        if ($oldIds->isNotEmpty()) {
            DB::table('sub_services')
                ->whereIn('id', $oldIds->toArray())
                ->update(['is_active' => true]);
        }
    }
};
