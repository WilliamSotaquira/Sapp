<?php
// database/migrations/2024_01_15_000000_create_service_families_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // Cambié 'order' por 'sort_order'
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_families');
    }
};
