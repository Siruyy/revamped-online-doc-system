<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')
                ->constrained('document_requests')
                ->cascadeOnDelete();
            $table->foreignId('document_type_id')
                ->constrained('document_types')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('copies')->default(1);
            $table->unsignedSmallInteger('page_count_snapshot')->default(1)
                ->comment('Snapshotted from document_types.default_page_count at time of request.');
            $table->decimal('fee_per_page_snapshot', 10, 2)->default(0)
                ->comment('Snapshotted per-page fee (or flat fee) at time of request.');
            $table->decimal('line_total', 10, 2)->default(0)
                ->comment('fee_per_page_snapshot * page_count_snapshot * copies (or flat * copies).');
            $table->timestamps();

            $table->index('document_request_id');
            $table->index('document_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_items');
    }
};
