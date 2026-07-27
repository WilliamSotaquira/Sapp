<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuts', function (Blueprint $table) {
            $table->string('status', 20)->default('closed')->after('end_date');
            $table->timestamp('closed_at')->nullable()->after('status');
        });

        // Mark the most recent cut per contract as 'open', rest as 'closed'
        $latestCutIds = DB::table('cuts')
            ->selectRaw('MAX(id) as id')
            ->groupBy('contract_id')
            ->pluck('id');

        if ($latestCutIds->isNotEmpty()) {
            DB::table('cuts')
                ->whereIn('id', $latestCutIds)
                ->update(['status' => 'open']);
        }

        // Set closed_at for all closed cuts to their end_date
        DB::table('cuts')
            ->where('status', 'closed')
            ->update(['closed_at' => DB::raw('end_date')]);
    }

    public function down(): void
    {
        Schema::table('cuts', function (Blueprint $table) {
            $table->dropColumn(['status', 'closed_at']);
        });
    }
};
