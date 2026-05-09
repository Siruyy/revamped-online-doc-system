<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_slips', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number', 32)->unique();
            $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('release_channel', 64);
            $table->date('claim_date')->nullable();
            $table->enum('state', ['pending', 'ready', 'released', 'expired', 'void'])->default('pending');

            $table->string('claimant_name', 150)->nullable();
            $table->string('claimant_id_reference', 100)->nullable();
            $table->boolean('is_proxy_release')->default(false);
            $table->string('authorization_type', 32)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->index('state');
            $table->index('claim_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_slips');
    }
};
