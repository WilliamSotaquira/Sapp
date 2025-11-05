<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_subservices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_family_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('sub_service_id')->constrained('sub_services')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Nombre de índice más corto para evitar el error
            $table->unique(
                ['service_family_id', 'service_id', 'sub_service_id'],
                'svc_subsvc_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_subservices');
    }
};
