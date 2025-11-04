<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('pending_at')->nullable()->after('status');
            $table->timestamp('accepted_at')->nullable()->after('pending_at');
            $table->timestamp('in_progress_at')->nullable()->after('accepted_at');
            $table->timestamp('resolved_at')->nullable()->after('in_progress_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->timestamp('cancelled_at')->nullable()->after('closed_at');
            $table->text('status_notes')->nullable()->after('cancelled_at');
        });
    }

    public function down()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'pending_at',
                'accepted_at',
                'in_progress_at',
                'resolved_at',
                'closed_at',
                'cancelled_at',
                'status_notes'
            ]);
        });
    }
};
