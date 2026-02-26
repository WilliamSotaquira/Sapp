<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->string('title', 400)->change();
        });

        Schema::table('standard_subtasks', function (Blueprint $table) {
            $table->string('title', 400)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->string('title', 255)->change();
        });

        Schema::table('standard_subtasks', function (Blueprint $table) {
            $table->string('title', 255)->change();
        });
    }
};

