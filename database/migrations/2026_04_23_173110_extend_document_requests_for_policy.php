<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('document_type_id');
            $table->unsignedInteger('page_count')->nullable()->after('quantity');
            $table->decimal('fee_snapshot', 10, 2)->default(0)->after('page_count');
            $table->string('intake_mode', 32)->default('online')->after('processing_stage');
            $table->json('extra_data')->nullable()->after('purpose');

            $table->timestamp('sla_start_at')->nullable()->after('approved_at');
            $table->timestamp('sla_paused_at')->nullable()->after('sla_start_at');
            $table->timestamp('sla_resumed_at')->nullable()->after('sla_paused_at');
            $table->string('sla_pause_reason', 64)->nullable()->after('sla_resumed_at');
            $table->date('expected_release_on')->nullable()->after('sla_pause_reason');

            $table->boolean('requires_hd_return')->default(false)->after('expected_release_on');
            $table->timestamp('hd_received_at')->nullable()->after('requires_hd_return');

            $table->boolean('transfer_exception_requested')->default(false)->after('hd_received_at');
            $table->boolean('transfer_exception_approved')->default(false)->after('transfer_exception_requested');
            $table->timestamp('transfer_exception_decided_at')->nullable()->after('transfer_exception_approved');

            $table->index('intake_mode');
            $table->index('expected_release_on');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndex(['intake_mode']);
            $table->dropIndex(['expected_release_on']);
            $table->dropColumn([
                'quantity',
                'page_count',
                'fee_snapshot',
                'intake_mode',
                'extra_data',
                'sla_start_at',
                'sla_paused_at',
                'sla_resumed_at',
                'sla_pause_reason',
                'expected_release_on',
                'requires_hd_return',
                'hd_received_at',
                'transfer_exception_requested',
                'transfer_exception_approved',
                'transfer_exception_decided_at',
            ]);
        });
    }
};
