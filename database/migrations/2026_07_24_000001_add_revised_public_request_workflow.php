<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_department_id')->constrained()->restrictOnDelete();
            $table->string('code', 24);
            $table->string('name', 180);
            $table->string('major', 120)->nullable();
            $table->string('level', 24)->default('college');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'major']);
            $table->index(['academic_department_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('academic_department_id')->nullable()->after('role')
                ->constrained()->nullOnDelete();
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->foreignId('academic_program_id')->nullable()->after('requester_course')
                ->constrained()->nullOnDelete();
            $table->string('academic_program_snapshot', 220)->nullable()->after('academic_program_id');
            $table->string('academic_department_code_snapshot', 24)->nullable()->after('academic_program_snapshot');
            $table->string('workflow_stage', 32)->default('submitted')->after('processing_stage');
            $table->json('requester_profile')->nullable()->after('extra_data');
            $table->string('fulfillment_method', 24)->default('pickup')->after('requester_profile');
            $table->text('delivery_address')->nullable()->after('fulfillment_method');
            $table->boolean('is_proxy_request')->default(false)->after('delivery_address');
            $table->string('tracking_access_hash', 255)->nullable()->after('is_proxy_request');
            $table->decimal('shipping_fee', 10, 2)->default(0)->after('fee_snapshot');
            $table->decimal('quote_total', 10, 2)->nullable()->after('shipping_fee');
            $table->text('quote_notes')->nullable()->after('quote_total');
            $table->foreignId('evaluated_by')->nullable()->after('quote_notes')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable()->after('evaluated_by');

            $table->index('workflow_stage');
            $table->index('academic_program_id');
        });

        Schema::table('document_request_items', function (Blueprint $table) {
            $table->boolean('authentication_requested')->default(false);
            $table->boolean('documentary_stamp_requested')->default(false);
            $table->string('semester_requested', 100)->nullable();
            $table->unsignedSmallInteger('evaluated_page_count')->nullable();
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->decimal('authentication_amount', 10, 2)->default(0);
            $table->decimal('documentary_stamp_amount', 10, 2)->default(0);
            $table->text('evaluation_notes')->nullable();
        });

        Schema::create('clearance_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->cascadeOnDelete();
            $table->string('office_code', 32);
            $table->string('label', 120);
            $table->unsignedSmallInteger('sequence');
            $table->string('department_code', 24)->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['clearance_id', 'office_code']);
            $table->index(['office_code', 'status']);
        });

        Schema::table('claim_slips', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('pdf_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('claim_slips', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::dropIfExists('clearance_steps');

        Schema::table('document_request_items', function (Blueprint $table) {
            $table->dropColumn([
                'authentication_requested',
                'documentary_stamp_requested',
                'semester_requested',
                'evaluated_page_count',
                'base_amount',
                'authentication_amount',
                'documentary_stamp_amount',
                'evaluation_notes',
            ]);
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign(['academic_program_id']);
            $table->dropForeign(['evaluated_by']);
            $table->dropColumn([
                'academic_program_id',
                'academic_program_snapshot',
                'academic_department_code_snapshot',
                'workflow_stage',
                'requester_profile',
                'fulfillment_method',
                'delivery_address',
                'is_proxy_request',
                'tracking_access_hash',
                'shipping_fee',
                'quote_total',
                'quote_notes',
                'evaluated_by',
                'evaluated_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_department_id');
        });

        Schema::dropIfExists('academic_programs');
        Schema::dropIfExists('academic_departments');
    }
};
