<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            // Verificar si la columna existe y tiene clave foránea
            if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
                // Eliminar la clave foránea si existe
                $table->dropForeign(['sub_service_id']);
                // Eliminar la columna
                $table->dropColumn('sub_service_id');
            }
        });
    }

    public function down()
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_service_id')->nullable()->after('service_subservice_id');
            $table->foreign('sub_service_id')->references('id')->on('sub_services');
        });
    }
};
