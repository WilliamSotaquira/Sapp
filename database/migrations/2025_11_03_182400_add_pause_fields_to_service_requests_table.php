<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Agregar campo para estado de pausa
            $table->boolean('is_paused')->default(false);
            $table->text('pause_reason')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->integer('total_paused_minutes')->default(0);

            // Actualizar el enum de status para incluir PAUSADA
            $table->enum('status', [
                'PENDIENTE',
                'ACEPTADA',
                'EN_PROCESO',
                'PAUSADA',  // NUEVO ESTADO
                'RESUELTA',
                'CERRADA',
                'CANCELADA'
            ])->default('PENDIENTE')->change();
        });
    }

    public function down()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'is_paused',
                'pause_reason',
                'paused_at',
                'resumed_at',
                'total_paused_minutes'
            ]);

            // Revertir el enum
            $table->enum('status', [
                'PENDIENTE',
                'ACEPTADA',
                'EN_PROCESO',
                'RESUELTA',
                'CERRADA',
                'CANCELADA'
            ])->default('PENDIENTE')->change();
        });
    }
};
