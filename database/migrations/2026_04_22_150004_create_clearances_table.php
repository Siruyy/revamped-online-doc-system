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
        Schema::create('clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('document_request_id')->nullable()->constrained('document_requests')->restrictOnDelete();

            $table->enum('teacher_status', ['pending', 'cleared', 'denied'])->default('pending');
            $table->text('teacher_remarks')->nullable();
            $table->foreignId('teacher_signed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('teacher_signed_at')->nullable();

            $table->enum('dean_status', ['pending', 'cleared', 'denied'])->default('pending');
            $table->text('dean_remarks')->nullable();
            $table->foreignId('dean_signed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('dean_signed_at')->nullable();

            $table->enum('accounting_status', ['pending', 'cleared', 'denied'])->default('pending');
            $table->text('accounting_remarks')->nullable();
            $table->foreignId('accounting_signed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('accounting_signed_at')->nullable();

            $table->enum('sao_status', ['pending', 'cleared', 'denied'])->default('pending');
            $table->text('sao_remarks')->nullable();
            $table->foreignId('sao_signed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('sao_signed_at')->nullable();

            $table->enum('overall_status', ['in_progress', 'completed', 'denied'])->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('uploaded_file_path')->nullable();
            $table->timestamps();

            $table->index('overall_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearances');
    }
};
