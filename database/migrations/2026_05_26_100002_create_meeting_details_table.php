<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')
                ->unique()
                ->constrained('service_requests')
                ->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->unsignedInteger('expected_duration_minutes');
            $table->string('location', 255)->nullable();
            $table->string('virtual_meeting_url', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_details');
    }
};
