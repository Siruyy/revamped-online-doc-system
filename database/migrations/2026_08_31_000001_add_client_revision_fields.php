<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->string('requester_last_name', 100)->nullable()->after('requester_name');
            $table->string('requester_first_name', 100)->nullable()->after('requester_last_name');
            $table->string('requester_middle_name', 100)->nullable()->after('requester_first_name');
            $table->string('requester_suffix', 32)->nullable()->after('requester_middle_name');
            $table->string('requester_year_level_status', 32)->nullable()->after('requester_year_level');
            $table->string('requester_claimant_name', 150)->nullable()->after('requester_profile');
            $table->string('representative_relationship', 100)->nullable()->after('requester_claimant_name');
            $table->string('owner_residence', 32)->nullable()->after('representative_relationship');
            $table->string('delivery_provider', 32)->nullable()->after('fulfillment_method');
            $table->string('courier_name', 120)->nullable()->after('delivery_address');
            $table->string('courier_tracking_number', 120)->nullable()->after('courier_name');
            $table->string('release_channel', 64)->nullable()->after('expected_release_on');
        });

        Schema::create('request_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_request_id')->unique()->constrained('document_requests')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('service_rating')->nullable();
            $table->text('comments')->nullable();
            $table->text('suggestions')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_feedback');

        Schema::table('document_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'requester_last_name',
                'requester_first_name',
                'requester_middle_name',
                'requester_suffix',
                'requester_year_level_status',
                'requester_claimant_name',
                'representative_relationship',
                'owner_residence',
                'delivery_provider',
                'courier_name',
                'courier_tracking_number',
                'release_channel',
            ]);
        });
    }
};
