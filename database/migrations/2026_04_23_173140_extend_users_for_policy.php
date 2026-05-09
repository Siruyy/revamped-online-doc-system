<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('academic_status', 32)->default('enrolled')->after('year_level');
            $table->timestamp('transferred_at')->nullable()->after('academic_status');
            $table->string('transferred_to', 200)->nullable()->after('transferred_at');
            $table->boolean('is_nstp')->default(false)->after('transferred_to');
            $table->boolean('is_graduate')->default(false)->after('is_nstp');

            $table->index('academic_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['academic_status']);
            $table->dropColumn([
                'academic_status',
                'transferred_at',
                'transferred_to',
                'is_nstp',
                'is_graduate',
            ]);
        });
    }
};
