<?php
// database/migrations/xxxx_xx_xx_xxxxxx_complete_missing_columns_in_service_level_agreements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            // Primero verifica qué columnas existen
            $existingColumns = Schema::getColumnListing('service_level_agreements');

            // Columnas que necesitamos
            $requiredColumns = [
                'description' => function() use ($table) {
                    $table->text('description')->nullable()->after('name');
                },
                'sub_service_id' => function() use ($table) {
                    $table->foreignId('sub_service_id')->after('description')->constrained('sub_services')->onDelete('cascade');
                },
                'criticality_level' => function() use ($table) {
                    $table->enum('criticality_level', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->after('sub_service_id');
                },
                'response_time_hours' => function() use ($table) {
                    $table->integer('response_time_hours')->after('criticality_level');
                },
                'resolution_time_hours' => function() use ($table) {
                    $table->integer('resolution_time_hours')->after('response_time_hours');
                },
                'availability_percentage' => function() use ($table) {
                    $table->decimal('availability_percentage', 5, 2)->after('resolution_time_hours');
                },
                'is_active' => function() use ($table) {
                    $table->boolean('is_active')->default(true)->after('availability_percentage');
                }
            ];

            // Agregar solo las columnas que faltan
            foreach ($requiredColumns as $columnName => $closure) {
                if (!in_array($columnName, $existingColumns)) {
                    $closure();
                }
            }

            // Agregar soft deletes si no existe
            if (!in_array('deleted_at', $existingColumns)) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_level_agreements', function (Blueprint $table) {
            // Solo eliminar las columnas que esta migración agregó
            $columnsToDrop = [
                'description',
                'sub_service_id',
                'criticality_level',
                'response_time_hours',
                'resolution_time_hours',
                'availability_percentage',
                'is_active',
                'deleted_at'
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('service_level_agreements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
