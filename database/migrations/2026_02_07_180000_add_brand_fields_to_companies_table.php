<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'primary_color')) {
                $table->string('primary_color', 7)->nullable()->after('address');
            }

            if (!Schema::hasColumn('companies', 'alternate_color')) {
                $table->string('alternate_color', 7)->nullable()->after('primary_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'alternate_color')) {
                $table->dropColumn('alternate_color');
            }

            if (Schema::hasColumn('companies', 'primary_color')) {
                $table->dropColumn('primary_color');
            }
        });
    }
};

