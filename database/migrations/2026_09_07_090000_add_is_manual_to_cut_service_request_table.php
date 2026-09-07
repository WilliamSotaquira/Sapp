<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la marca de asociación manual al pivote cut_service_request.
 *
 * Cuando is_manual = true, el corte fue fijado a mano por un técnico y el
 * recálculo automático por fecha de compleción NO debe pisarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cut_service_request', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('service_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('cut_service_request', function (Blueprint $table) {
            $table->dropColumn('is_manual');
        });
    }
};
