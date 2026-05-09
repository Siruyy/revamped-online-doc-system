<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('default_page_count')->default(1)
                ->after('processing_days')
                ->comment('Admin-defined number of pages used to calculate per-page fees.');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('default_page_count');
        });
    }
};
