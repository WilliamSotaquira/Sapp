<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca explícita del subservicio de CONTROL por contrato (Opción B).
 *
 * Cuando el líder delega una solicitud a un técnico, la solicitud se reclasifica
 * al "subservicio de control/verificación" de su contrato. Como los servicios y
 * subservicios cambian por entidad/contrato, se marca de forma explícita cuál es
 * el subservicio de control en cada uno (en vez de adivinarlo por nombre).
 *
 * Debe existir a lo sumo UN subservicio de control por contrato; la resolución
 * se hace subiendo por service -> family -> contract.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_services', function (Blueprint $table) {
            if (!Schema::hasColumn('sub_services', 'is_control')) {
                $table->boolean('is_control')
                    ->default(false)
                    ->after('is_active')
                    ->comment('Subservicio usado para reclasificar solicitudes delegadas como control/verificación del líder.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sub_services', function (Blueprint $table) {
            if (Schema::hasColumn('sub_services', 'is_control')) {
                $table->dropColumn('is_control');
            }
        });
    }
};
