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
    Schema::create('sub_services', function (Blueprint $table) {
        $table->id();
        $table->foreignId('service_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->string('code');
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->decimal('cost', 10, 2)->nullable();
        $table->integer('order')->default(0);
        $table->timestamps();
        $table->softDeletes();

        $table->unique(['service_id', 'code']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_services');
    }
};
