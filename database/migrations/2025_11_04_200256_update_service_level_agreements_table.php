<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            // Primero agregar la nueva columna
            $table->foreignId('service_subservice_id')->nullable()->after('id');

            // Crear la foreign key temporalmente nullable
            $table->foreign('service_subservice_id')->references('id')->on('service_subservices')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            $table->dropForeign(['service_subservice_id']);
            $table->dropColumn('service_subservice_id');
        });
    }
};
