<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fortalecer tabla projects con campos para gestión de proyectos de desarrollo.
     * Agregar project_id nullable a service_requests para vinculación.
     */
    public function up(): void
    {
        // 1. Enriquecer tabla projects
        Schema::table('projects', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->foreignId('company_id')->nullable()->after('description')->constrained()->nullOnDelete();
            $table->date('start_date')->nullable()->after('status');
            $table->date('expected_end_date')->nullable()->after('start_date');
            $table->date('actual_end_date')->nullable()->after('expected_end_date');
            $table->foreignId('created_by')->nullable()->after('actual_end_date')->constrained('users')->nullOnDelete();

            $table->index('company_id');
            $table->index('status');
        });

        // 2. Agregar project_id a service_requests
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['description', 'company_id', 'start_date', 'expected_end_date', 'actual_end_date', 'created_by']);
        });
    }
};
