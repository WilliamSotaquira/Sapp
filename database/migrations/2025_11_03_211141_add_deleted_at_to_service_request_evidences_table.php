<?php
// database/migrations/2025_11_04_add_deleted_at_to_service_request_evidences_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_request_evidences', function (Blueprint $table) {
            if (!Schema::hasColumn('service_request_evidences', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::table('service_request_evidences', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
