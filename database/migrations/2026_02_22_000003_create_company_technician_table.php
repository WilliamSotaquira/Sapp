<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_technician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('technicians')->cascadeOnDelete();
            $table->string('institutional_email')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'technician_id']);
            $table->index(['technician_id', 'company_id']);
        });

        // Migrar asociaciones existentes desde company_user -> technicians
        $rows = DB::table('company_user as cu')
            ->join('technicians as t', 't.user_id', '=', 'cu.user_id')
            ->leftJoin('company_technician as ct', function ($join) {
                $join->on('ct.company_id', '=', 'cu.company_id')
                    ->on('ct.technician_id', '=', 't.id');
            })
            ->whereNull('ct.id')
            ->select(
                'cu.company_id',
                't.id as technician_id',
                'cu.entity_email as institutional_email',
                'cu.entity_position as position',
                'cu.created_at',
                'cu.updated_at'
            )
            ->get();

        if ($rows->isNotEmpty()) {
            DB::table('company_technician')->insert(
                $rows->map(fn ($row) => (array) $row)->all()
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_technician');
    }
};

