<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('requester_name', 150)->nullable()->after('user_id');
            $table->string('requester_email', 150)->nullable()->after('requester_name');
            $table->string('requester_contact_number', 30)->nullable()->after('requester_email');
            $table->string('requester_student_id', 50)->nullable()->after('requester_contact_number');
            $table->string('requester_course', 100)->nullable()->after('requester_student_id');
            $table->unsignedTinyInteger('requester_year_level')->nullable()->after('requester_course');

            $table->index('requester_student_id');
            $table->index('requester_email');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndex(['requester_student_id']);
            $table->dropIndex(['requester_email']);
            $table->dropColumn([
                'requester_name',
                'requester_email',
                'requester_contact_number',
                'requester_student_id',
                'requester_course',
                'requester_year_level',
            ]);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
