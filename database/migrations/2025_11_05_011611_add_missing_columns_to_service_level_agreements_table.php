<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_missing_columns_to_service_level_agreements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            // Verificar y agregar cada columna faltante

            if (!Schema::hasColumn('service_level_agreements', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
                $table->foreignId('sub_service_id')->after('description')->constrained('sub_services')->onDelete('cascade');
            }

            if (!Schema::hasColumn('service_level_agreements', 'criticality_level')) {
                $table->enum('criticality_level', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->after('sub_service_id');
            }

            if (!Schema::hasColumn('service_level_agreements', 'response_time_hours')) {
                $table->integer('response_time_hours')->after('criticality_level');
            }

            if (!Schema::hasColumn('service_level_agreements', 'resolution_time_hours')) {
                $table->integer('resolution_time_hours')->after('response_time_hours');
            }

            if (!Schema::hasColumn('service_level_agreements', 'availability_percentage')) {
                $table->decimal('availability_percentage', 5, 2)->after('resolution_time_hours');
            }

            if (!Schema::hasColumn('service_level_agreements', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('availability_percentage');
            }

            // Agregar soft deletes si no existe
            if (!Schema::hasColumn('service_level_agreements', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            // Solo eliminar las columnas que agregamos, no todas
            $columns = [
                'description',
                'sub_service_id',
                'criticality_level',
                'response_time_hours',
                'resolution_time_hours',
                'availability_percentage',
                'is_active',
                'deleted_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('service_level_agreements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
