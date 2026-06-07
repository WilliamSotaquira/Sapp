<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('request_type_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('service_request_id')->nullable()->after('request_type_id');

            $table->foreign('request_type_id')
                ->references('id')
                ->on('request_types');

            $table->foreign('service_request_id')
                ->references('id')
                ->on('service_requests')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['service_request_id']);
            $table->dropForeign(['request_type_id']);
            $table->dropColumn(['service_request_id', 'request_type_id']);
        });
    }
};
