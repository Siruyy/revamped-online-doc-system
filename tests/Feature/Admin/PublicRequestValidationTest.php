<?php

namespace Tests\Feature\Admin;

use App\Events\RequestApproved;
use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            RequestApproved::class,
            RequestDenied::class,
            RequestStageUpdated::class,
        ]);
    }

    public function test_approve_with_payment_requires_pending_request(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(requestStatus: 'approved');

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertSessionHasErrors('request');

        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'pending_approval',
        ]);
    }

    public function test_approve_with_payment_requires_pending_approval_payment(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(paymentStatus: 'pending');

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    public function test_approve_with_payment_requires_all_requirements_validated(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(requirementStatus: 'submitted');

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertSessionHasErrors('requirement');

        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'pending_approval',
        ]);
    }

    public function test_admin_can_approve_public_request_package_and_create_clearance(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage();

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('clearances', [
            'user_id' => null,
            'document_request_id' => $request->id,
            'overall_status' => 'in_progress',
        ]);

        Notification::assertSentOnDemand(
            WorkflowStatusNotification::class,
            fn (WorkflowStatusNotification $notification, array $channels, object $notifiable): bool => $channels === ['mail']
                && ($notifiable->routes['mail'] ?? null) === 'public@example.test'
                && ($notification->toArray($notifiable)['type'] ?? null) === 'request_approved'
                && ! array_key_exists('receipt_path', $notification->toArray($notifiable))
                && ! array_key_exists('file_path', $notification->toArray($notifiable)),
        );
    }

    public function test_superadmin_can_approve_public_request_package(): void
    {
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage();

        $this->actingAs($superadmin)
            ->post(route('superadmin.requests.approve-with-payment', $request))
            ->assertRedirect();

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approved_by' => $superadmin->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'approved',
            'approved_by' => $superadmin->id,
        ]);
    }

    public function test_admin_can_validate_requirement_then_approve_public_request_package(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(requirementStatus: 'submitted');
        $requirement = $request->requirements()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.requests.requirements.validate', [$request, $requirement]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('request_requirements', [
            'id' => $requirement->id,
            'status' => 'validated',
            'validated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'approved',
        ]);
    }

    public function test_student_request_is_rejected_by_public_package_endpoints(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $student = User::factory()->student()->create(['status' => 'active']);
        $request = DocumentRequest::factory()->for($student)->pending()->create(['intake_mode' => 'online']);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertSessionHasErrors('request');

        $this->actingAs($admin)
            ->post(route('admin.requests.deny-with-payment', $request), [
                'denial_reason' => 'Use standard student workflow.',
            ])
            ->assertSessionHasErrors('request');

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    public function test_public_request_is_rejected_by_legacy_approve_and_deny_routes(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage();

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $request))
            ->assertSessionHasErrors('request');

        $this->actingAs($admin)
            ->post(route('admin.requests.deny', $request), [
                'denial_reason' => 'Use package workflow.',
            ])
            ->assertSessionHasErrors('request');

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    public function test_deny_with_payment_requires_reason(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(requirementStatus: 'submitted');

        $this->actingAs($admin)
            ->post(route('admin.requests.deny-with-payment', $request), [])
            ->assertSessionHasErrors('denial_reason');

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_deny_public_request_package_and_tracking_shows_reason(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(requirementStatus: 'submitted');

        $this->actingAs($admin)
            ->post(route('admin.requests.deny-with-payment', $request), [
                'denial_reason' => 'Receipt image is unreadable.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'denied',
            'denial_reason' => 'Receipt image is unreadable.',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'denied',
            'denial_reason' => 'Receipt image is unreadable.',
            'approved_by' => $admin->id,
        ]);

        $this->post(route('track-document.show'), [
            'reference_no' => $request->reference_no,
        ])->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackResult')
                ->where('result.status', 'denied')
                ->where('result.denial_reason', 'Receipt image is unreadable.'));

        Notification::assertSentOnDemand(
            WorkflowStatusNotification::class,
            fn (WorkflowStatusNotification $notification, array $channels, object $notifiable): bool => $channels === ['mail']
                && ($notifiable->routes['mail'] ?? null) === 'public@example.test'
                && ($notification->toArray($notifiable)['type'] ?? null) === 'request_denied'
                && ($notification->toArray($notifiable)['reason'] ?? null) === 'Receipt image is unreadable.',
        );
    }

    public function test_public_requestor_email_is_not_attempted_when_email_is_absent(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create(['status' => 'active']);
        $request = $this->createPublicRequestPackage(requesterEmail: null);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve-with-payment', $request))
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentOnDemandTimes(WorkflowStatusNotification::class, 0);
    }

    private function createPublicRequestPackage(
        string $requestStatus = 'pending',
        string $paymentStatus = 'pending_approval',
        string $requirementStatus = 'validated',
        ?string $requesterEmail = 'public@example.test',
    ): DocumentRequest {
        $documentType = DocumentType::factory()->create([
            'name' => 'Transcript of Records',
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);
        $request = DocumentRequest::factory()->for($documentType)->create([
            'user_id' => null,
            'requester_name' => 'Public Requestor',
            'requester_email' => $requesterEmail,
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'SVCI-2026-0001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 4,
            'status' => $requestStatus,
            'processing_stage' => $requestStatus === 'approved' ? 'processing' : 'not_started',
            'intake_mode' => 'public',
            'fee_snapshot' => 150,
        ]);

        Payment::factory()->for($request)->create([
            'user_id' => null,
            'status' => $paymentStatus,
            'total_amount' => 150,
            'payment_method' => 'GCash',
            'reference_number' => 'GCASH-12345',
            'receipt_path' => "payment-receipts/public/{$request->id}/receipt.jpg",
            'submitted_at' => now(),
        ]);

        RequestRequirement::query()->create([
            'document_request_id' => $request->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy',
            'status' => $requirementStatus,
            'file_path' => "request-requirements/public/{$request->id}/valid-id.jpg",
            'validated_by' => $requirementStatus === 'validated' ? User::factory()->admin()->create()->id : null,
            'validated_at' => $requirementStatus === 'validated' ? now() : null,
        ]);

        return $request;
    }
}
