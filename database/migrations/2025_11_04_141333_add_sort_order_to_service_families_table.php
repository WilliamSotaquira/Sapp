<?php
// database/migrations/2025_11_04_xxxxxx_add_sort_order_to_service_families_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_families', function (Blueprint $table) {
            if (!Schema::hasColumn('service_families', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });
    }

    public function down()
    {
        Schema::table('service_families', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
