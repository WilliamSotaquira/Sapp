<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_cloud_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_family_id')->constrained('service_families')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'service_family_id'], 'family_cloud_links_company_family_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_cloud_links');
    }
};
