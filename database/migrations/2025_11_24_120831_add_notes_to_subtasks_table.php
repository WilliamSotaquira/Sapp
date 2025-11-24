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
            if (!Schema::hasColumn('subtasks', 'notes')) {
                $table->text('notes')->nullable()->after('title');
            }

            if (!Schema::hasColumn('subtasks', 'estimated_minutes')) {
                $table->integer('estimated_minutes')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            if (Schema::hasColumn('subtasks', 'estimated_minutes')) {
                $table->dropColumn('estimated_minutes');
            }

            if (Schema::hasColumn('subtasks', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
