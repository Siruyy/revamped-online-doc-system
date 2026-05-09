<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->unique()->after('id');
            $table->string('fee_formula', 32)->default('flat')->after('fee');
            $table->string('submission_window', 64)->nullable()->after('processing_days');
            $table->string('release_channel', 64)->nullable()->after('submission_window');
            $table->json('offices')->nullable()->after('release_channel');
            $table->json('requirements')->nullable()->after('offices');
            $table->json('flags')->nullable()->after('requirements');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code',
                'fee_formula',
                'submission_window',
                'release_channel',
                'offices',
                'requirements',
                'flags',
            ]);
        });
    }
};
