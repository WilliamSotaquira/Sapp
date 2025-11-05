<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('service_level_agreements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('service_family_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->enum('criticality_level', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA']);
        $table->integer('acceptance_time_minutes')->default(30);
        $table->integer('response_time_minutes');
        $table->integer('resolution_time_minutes');
        $table->text('conditions')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_level_agreements');
    }
};
