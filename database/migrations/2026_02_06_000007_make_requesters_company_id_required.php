<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Backfill company_id desde solicitudes asociadas
        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE requesters
                SET company_id = (
                    SELECT sr.company_id
                    FROM service_requests sr
                    WHERE sr.requester_id = requesters.id
                      AND sr.company_id IS NOT NULL
                    ORDER BY sr.id DESC
                    LIMIT 1
                )
                WHERE company_id IS NULL
            ");
        } else {
            DB::statement("
                UPDATE requesters r
                JOIN (
                    SELECT requester_id, MAX(company_id) AS company_id
                    FROM service_requests
                    WHERE company_id IS NOT NULL
                    GROUP BY requester_id
                ) x ON x.requester_id = r.id
                SET r.company_id = x.company_id
                WHERE r.company_id IS NULL
            ");
        }

        // Fallback: asignar al primer company disponible si aún hay nulls
        $fallbackCompanyId = DB::table('companies')->orderBy('id')->value('id');
        if ($fallbackCompanyId) {
            DB::table('requesters')
                ->whereNull('company_id')
                ->update(['company_id' => $fallbackCompanyId]);
        }

        // Enforce NOT NULL (omitido en sqlite)
        if ($driver === 'mysql') {
            Schema::table('requesters', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
            DB::statement('ALTER TABLE requesters MODIFY company_id BIGINT UNSIGNED NOT NULL');
            Schema::table('requesters', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE requesters ALTER COLUMN company_id SET NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE requesters ALTER COLUMN company_id BIGINT NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            Schema::table('requesters', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
            DB::statement('ALTER TABLE requesters MODIFY company_id BIGINT UNSIGNED NULL');
            Schema::table('requesters', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE requesters ALTER COLUMN company_id DROP NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE requesters ALTER COLUMN company_id BIGINT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite: no-op (mantiene nullable).
        }
    }
};
