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
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'scheduled_order')) {
                $table->integer('scheduled_order')->default(0)->after('scheduled_start_time');
                $table->index(['scheduled_date', 'technician_id', 'scheduled_order'], 'tasks_schedule_order_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'scheduled_order')) {
                $table->dropIndex('tasks_schedule_order_index');
                $table->dropColumn('scheduled_order');
            }
        });
    }
};
