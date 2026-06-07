<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_detail_id')
                ->constrained('meeting_details')
                ->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->enum('role', ['organizador', 'participante', 'invitado'])->default('participante');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('attended')->nullable();
            $table->timestamps();

            $table->unique(['meeting_detail_id', 'email'], 'uq_mp_email_meeting');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};
