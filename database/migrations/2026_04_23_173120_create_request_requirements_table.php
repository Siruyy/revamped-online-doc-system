<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
            $table->string('requirement_key', 64);
            $table->string('label', 150);
            $table->enum('status', ['missing', 'submitted', 'validated', 'rejected'])->default('missing');
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['document_request_id', 'requirement_key']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_requirements');
    }
};
