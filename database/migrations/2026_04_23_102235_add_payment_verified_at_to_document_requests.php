<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            // Set when admin approves the student's payment receipt.
            // Once set, the request appears in the admin review queue.
            $table->timestamp('payment_verified_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn('payment_verified_at');
        });
    }
};
