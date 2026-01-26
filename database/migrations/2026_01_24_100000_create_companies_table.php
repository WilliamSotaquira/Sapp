<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nit')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'name']);
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
