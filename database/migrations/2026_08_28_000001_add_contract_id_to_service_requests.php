<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 del modelo orientado a contrato.
 *
 * Agrega contract_id explícito a service_requests (antes se derivaba de
 * sub_service -> family -> contract) y lo puebla para el histórico.
 *
 * Además corrige las solicitudes cuyo company_id no coincide con la empresa
 * dueña del contrato de su subservicio (mismatch multi-entidad), alineando
 * company_id al de la cadena real del catálogo.
 *
 * No cambia el comportamiento de la app: solo añade la columna y consistencia
 * de datos. La lógica que consume contract_id se activa en fases posteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('service_requests', 'contract_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('company_id');
                $table->index('contract_id');
            });
        }

        // 1. Poblar contract_id desde la cadena del catálogo (sub_service -> family -> contract)
        DB::statement("
            UPDATE service_requests sr
            JOIN sub_services ss ON ss.id = sr.sub_service_id
            JOIN services s ON s.id = ss.service_id
            JOIN service_families sf ON sf.id = s.service_family_id
            SET sr.contract_id = sf.contract_id
            WHERE sr.contract_id IS NULL
        ");

        // 2. Corregir company_id de solicitudes con mismatch:
        //    alinear al company dueño del contrato del subservicio (fuente de verdad del catálogo).
        DB::statement("
            UPDATE service_requests sr
            JOIN contracts ct ON ct.id = sr.contract_id
            SET sr.company_id = ct.company_id
            WHERE sr.contract_id IS NOT NULL
              AND sr.company_id <> ct.company_id
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_requests', 'contract_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropIndex(['contract_id']);
                $table->dropColumn('contract_id');
            });
        }
    }
};
