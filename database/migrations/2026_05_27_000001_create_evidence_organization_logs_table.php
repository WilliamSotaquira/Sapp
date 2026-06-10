<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_organization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('service_request_evidences');
            $table->foreignId('cut_id')->constrained('cuts');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_path', 500);
            $table->string('destination_path', 500);
            $table->enum('result', ['success', 'failed', 'skipped']);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['cut_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_organization_logs');
    }
};
