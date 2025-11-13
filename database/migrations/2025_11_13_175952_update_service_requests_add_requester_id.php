<?php
// database/migrations/xxxx_xx_xx_xxxxxx_update_service_requests_add_requester_id.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Agregar campo requester_id
            $table->foreignId('requester_id')->nullable()->after('id')->constrained()->onDelete('restrict');

            // Mantener requested_by para auditoría pero hacerlo nullable
            $table->foreignId('requested_by')->nullable()->change();

            // Índices
            $table->index('requester_id');
        });
    }

    public function down()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['requester_id']);
            $table->dropColumn('requester_id');
            $table->foreignId('requested_by')->nullable(false)->change();
        });
    }
};
