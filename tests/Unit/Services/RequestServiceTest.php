<?php

namespace Tests\Unit\Services;

use App\Events\RequestApproved;
use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Events\RequestSubmitted;
use App\Models\ActivityLog;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\RequestCancelledNotification;
use App\Services\RequestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_request_payment_items_and_activity_log(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create([
            'fee' => 75,
            'default_page_count' => 2,
            'is_active' => true,
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $result = $this->service()->createMultiItemRequest($student, [
            'items' => [[
                'document_type_id' => $documentType->id,
                'copies' => 3,
            ]],
            'purpose' => 'Employment',
        ]);

        $request = $result['request'];
        $payment = $result['payment'];

        $this->assertSame('pending', $request->status);
        $this->assertSame('not_started', $request->processing_stage);
        $this->assertSame('450.00', $request->fee_snapshot);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('450.00', $payment->total_amount);
        $this->assertDatabaseHas('document_request_items', [
            'document_request_id' => $request->id,
            'document_type_id' => $documentType->id,
            'copies' => 3,
            'page_count_snapshot' => 2,
            'fee_per_page_snapshot' => 75,
            'line_total' => 450,
        ]);
        $this->assertDatabaseHas('request_requirements', [
            'document_request_id' => $request->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'status' => 'missing',
        ]);
        $this->assertSame('request_submitted', ActivityLog::query()->latest('id')->value('action'));
        Event::assertDispatched(RequestSubmitted::class);
    }

    public function test_it_approves_pending_request_and_starts_sla_when_allowed(): void
    {
        Event::fake([RequestApproved::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create(['processing_days' => 3]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->pending()->create([
            'requires_hd_return' => false,
        ]);

        $approved = $this->service()->approveRequest($request, $admin);

        $this->assertSame('approved', $approved->status);
        $this->assertSame('processing', $approved->processing_stage);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertNotNull($approved->sla_start_at);
        $this->assertNotNull($approved->expected_release_on);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'request_approved',
            'user_id' => $admin->id,
            'affected_user_id' => $student->id,
        ]);
        Event::assertDispatched(RequestApproved::class);
    }

    public function test_it_denies_pending_or_approved_request_with_reason(): void
    {
        Event::fake([RequestDenied::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();

        $denied = $this->service()->denyRequest($request, $admin, 'Requirements mismatch');

        $this->assertSame('denied', $denied->status);
        $this->assertSame('not_started', $denied->processing_stage);
        $this->assertSame('Requirements mismatch', $denied->denial_reason);
        $this->assertSame($admin->id, $denied->approved_by);
        Event::assertDispatched(RequestDenied::class);
    }

    public function test_it_cancels_request_without_uploaded_receipt(): void
    {
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->pending()->create([
            'processing_stage' => 'processing',
        ]);
        Payment::factory()->for($student)->for($request)->pending()->create([
            'receipt_path' => null,
        ]);

        $cancelled = $this->service()->cancelRequest($request, $student);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertSame('not_started', $cancelled->processing_stage);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'request_cancelled',
            'user_id' => $student->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_it_rejects_cancellation_after_receipt_upload(): void
    {
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        Payment::factory()->for($student)->for($request)->pendingApproval()->create([
            'receipt_path' => "payment-receipts/{$student->id}/receipt.pdf",
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('receipt was already uploaded');

        $this->service()->cancelRequest($request, $student);
    }

    public function test_it_rejects_cancellation_by_non_owner(): void
    {
        $owner = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($owner)->pending()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only the request owner can cancel this request.');

        $this->service()->cancelRequest($request, $otherStudent);
    }

    public function test_request_cancelled_notification_is_queued(): void
    {
        $this->assertContains(ShouldQueue::class, class_implements(RequestCancelledNotification::class));
    }

    public function test_it_allows_approved_request_to_move_to_processing_without_payment_or_clearance(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create([
            'processing_stage' => 'not_started',
        ]);

        $updated = $this->service()->updateStage($request, $admin, 'processing');

        $this->assertSame('processing', $updated->processing_stage);
        $this->assertSame('approved', $updated->status);
        Event::assertDispatched(RequestStageUpdated::class);
    }

    public function test_it_rejects_ready_for_pickup_without_approved_payment(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create(['flags' => ['no_clearance_needed']]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        Payment::factory()->for($student)->for($request)->pending()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Approve payment before moving this request to ready for pickup.');

        $this->service()->updateStage($request, $admin, 'ready_for_pickup');
    }

    public function test_it_rejects_release_when_required_clearance_is_not_completed(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create(['flags' => []]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();
        Clearance::factory()->for($student)->for($request)->inProgress()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Complete clearance before moving this request to released.');

        $this->service()->updateStage($request, $admin, 'released');
    }

    public function test_it_rejects_ready_for_pickup_when_multi_item_request_requires_incomplete_clearance(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $primaryType = DocumentType::factory()->create(['flags' => ['no_clearance_needed']]);
        $clearanceType = DocumentType::factory()->create(['flags' => []]);
        $request = DocumentRequest::factory()->for($student)->for($primaryType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();
        Clearance::factory()->for($student)->for($request)->inProgress()->create();
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $primaryType->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 50,
            'line_total' => 50,
        ]);
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $clearanceType->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 75,
            'line_total' => 75,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Complete clearance before moving this request to ready for pickup.');

        $this->service()->updateStage($request, $admin, 'ready_for_pickup');
    }

    public function test_it_releases_multi_item_request_after_required_clearance_is_completed(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $primaryType = DocumentType::factory()->create(['flags' => ['no_clearance_needed']]);
        $clearanceType = DocumentType::factory()->create(['flags' => []]);
        $request = DocumentRequest::factory()->for($student)->for($primaryType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();
        Clearance::factory()->for($student)->for($request)->completed()->create();
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $primaryType->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 50,
            'line_total' => 50,
        ]);
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $clearanceType->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 75,
            'line_total' => 75,
        ]);

        $updated = $this->service()->updateStage($request, $admin, 'released');

        $this->assertSame('released', $updated->processing_stage);
        $this->assertSame('completed', $updated->status);
        $this->assertNotNull($updated->released_at);
        Event::assertDispatched(RequestStageUpdated::class);
    }

    public function test_it_allows_multi_item_request_without_clearance_needed_to_move_to_ready_for_pickup_without_clearance(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $firstType = DocumentType::factory()->create([
            'flags' => ['no_clearance_needed'],
            'release_channel' => 'registrar_window_9',
        ]);
        $secondType = DocumentType::factory()->create(['flags' => ['no_clearance_needed']]);
        $request = DocumentRequest::factory()->for($student)->for($firstType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $firstType->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 50,
            'line_total' => 50,
        ]);
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $secondType->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 75,
            'line_total' => 75,
        ]);

        $updated = $this->service()->updateStage($request, $admin, 'ready_for_pickup');

        $this->assertSame('ready_for_pickup', $updated->processing_stage);
        $this->assertSame('approved', $updated->status);
        $this->assertDatabaseMissing('clearances', [
            'document_request_id' => $request->id,
        ]);
        Event::assertDispatched(RequestStageUpdated::class);
    }

    public function test_it_updates_stage_and_issues_claim_slip_when_ready_for_pickup(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create([
            'flags' => ['no_clearance_needed'],
            'release_channel' => 'registrar_window_9',
        ]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();

        $updated = $this->service()->updateStage($request, $admin, 'ready_for_pickup');

        $this->assertSame('ready_for_pickup', $updated->processing_stage);
        $this->assertSame('approved', $updated->status);
        $this->assertDatabaseHas('claim_slips', [
            'document_request_id' => $request->id,
            'user_id' => $student->id,
            'release_channel' => 'registrar_window_9',
            'state' => 'ready',
        ]);
        Event::assertDispatched(RequestStageUpdated::class);
    }

    public function test_it_releases_request_after_approved_payment_and_required_clearance_is_completed(): void
    {
        Event::fake([RequestStageUpdated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create(['flags' => []]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();
        Clearance::factory()->for($student)->for($request)->completed()->create();

        $updated = $this->service()->updateStage($request, $admin, 'released');

        $this->assertSame('released', $updated->processing_stage);
        $this->assertSame('completed', $updated->status);
        $this->assertNotNull($updated->released_at);
        Event::assertDispatched(RequestStageUpdated::class);
    }

    private function service(): RequestService
    {
        return $this->app->make(RequestService::class);
    }
}
