<?php

namespace Tests\Feature\Notifications;

use App\Events\ClearanceCompleted;
use App\Events\ClearanceCreated;
use App\Events\ClearanceUpdated;
use App\Events\PaymentApproved;
use App\Events\PaymentDenied;
use App\Events\PaymentSubmitted;
use App\Events\RequestApproved;
use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Events\RequestSubmitted;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BrandedResetPasswordNotification;
use App\Notifications\ClearanceCompletedNotification;
use App\Notifications\RegistrationApprovedNotification;
use App\Notifications\RegistrationRejectedNotification;
use App\Notifications\RegistrationSubmittedNotification;
use App\Notifications\RequestCancelledNotification;
use App\Notifications\WorkflowStatusNotification;
use App\Services\ClearanceService;
use App\Services\PaymentService;
use App\Services\RequestService;
use App\Support\ClearanceSignatories;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BroadcastNotificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_submission_dispatches_event_and_notifies_admins(): void
    {
        $student = $this->activeUser('student');
        $admin = $this->activeUser('admin');
        $superadmin = $this->activeUser('superadmin');
        $documentType = DocumentType::factory()->create(['fee' => 100, 'default_page_count' => 1]);

        Event::fake([RequestSubmitted::class]);
        NotificationFake::fake();

        app(RequestService::class)->createRequestBatch($student, [$documentType->id], 'Board exam');

        Event::assertDispatched(RequestSubmitted::class, fn (RequestSubmitted $event) => $event->studentId === $student->id);
        $this->assertNotificationSentWithType($admin, 'request_submitted');
        $this->assertNotificationSentWithType($superadmin, 'request_submitted');
    }

    public function test_wizard_request_submission_dispatches_event_and_notifies_admins(): void
    {
        $student = $this->activeUser('student');
        $admin = $this->activeUser('admin');
        $superadmin = $this->activeUser('superadmin');
        $documentType = DocumentType::factory()->create(['fee' => 150, 'default_page_count' => 2]);

        Event::fake([RequestSubmitted::class]);
        NotificationFake::fake();

        $result = app(RequestService::class)->createMultiItemRequest($student, [
            'items' => [[
                'document_type_id' => $documentType->id,
                'copies' => 2,
            ]],
            'purpose' => 'Scholarship application',
        ]);

        Event::assertDispatched(RequestSubmitted::class, fn (RequestSubmitted $event) => $event->requestIds === [$result['request']->id]
            && $event->paymentId === $result['payment']->id
            && $event->studentId === $student->id);
        $this->assertNotificationSentWithType($admin, 'request_submitted');
        $this->assertNotificationSentWithType($superadmin, 'request_submitted');
    }

    public function test_request_approval_dispatches_event_and_notifies_student(): void
    {
        $admin = $this->activeUser('admin');
        $student = $this->activeUser('student');
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        Event::fake([RequestApproved::class]);
        NotificationFake::fake();

        app(RequestService::class)->approveRequest($request, $admin);

        Event::assertDispatched(RequestApproved::class, fn (RequestApproved $event) => $event->documentRequestId === $request->id && $event->studentId === $student->id);
        $this->assertNotificationSentWithType($student, 'request_approved');
    }

    public function test_request_denial_dispatches_event_and_notifies_student(): void
    {
        $admin = $this->activeUser('admin');
        $student = $this->activeUser('student');
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        Event::fake([RequestDenied::class]);
        NotificationFake::fake();

        app(RequestService::class)->denyRequest($request, $admin, 'Invalid records');

        Event::assertDispatched(RequestDenied::class, fn (RequestDenied $event) => $event->documentRequestId === $request->id && $event->reason === 'Invalid records');
        $this->assertNotificationSentWithType($student, 'request_denied');
    }

    public function test_request_stage_update_dispatches_event_and_notifies_student(): void
    {
        $admin = $this->activeUser('admin');
        $student = $this->activeUser('student');
        $documentType = DocumentType::factory()->create(['flags' => ['no_clearance_needed']]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();

        Event::fake([RequestStageUpdated::class]);
        NotificationFake::fake();

        app(RequestService::class)->updateStage($request, $admin, 'ready_for_pickup');

        Event::assertDispatched(RequestStageUpdated::class, fn (RequestStageUpdated $event) => $event->documentRequestId === $request->id && $event->processingStage === 'ready_for_pickup');
        $this->assertNotificationSentWithType($student, 'request_stage_updated');
    }

    public function test_payment_submission_dispatches_event_and_notifies_admins(): void
    {
        Storage::fake('local');
        $student = $this->activeUser('student');
        $admin = $this->activeUser('admin');
        $superadmin = $this->activeUser('superadmin');
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        Event::fake([PaymentSubmitted::class]);
        NotificationFake::fake();

        app(PaymentService::class)->uploadReceipt($payment, UploadedFile::fake()->image('receipt.jpg'), 'GCash', 'GCASH-123');

        Event::assertDispatched(PaymentSubmitted::class, fn (PaymentSubmitted $event) => $event->paymentId === $payment->id && $event->studentId === $student->id);
        $this->assertNotificationSentWithType($admin, 'payment_submitted');
        $this->assertNotificationSentWithType($superadmin, 'payment_submitted');
    }

    public function test_payment_approval_dispatches_events_and_notifies_student_and_department_roles(): void
    {
        $admin = $this->activeUser('admin');
        $student = $this->activeUser('student');
        $officers = collect(ClearanceSignatories::roles())
            ->map(fn (string $role): User => $this->activeUser($role))
            ->all();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->pendingApproval()->create();

        Event::fake([PaymentApproved::class, ClearanceCreated::class]);
        NotificationFake::fake();

        app(PaymentService::class)->approve($payment, $admin);

        Event::assertDispatched(PaymentApproved::class, fn (PaymentApproved $event) => $event->paymentId === $payment->id && $event->studentId === $student->id);
        Event::assertDispatched(ClearanceCreated::class, fn (ClearanceCreated $event) => $event->studentId === $student->id && $event->documentRequestId === $request->id);
        $this->assertNotificationSentWithType($student, 'payment_approved');

        foreach ($officers as $officer) {
            $this->assertNotificationSentWithType($officer, 'clearance_created');
        }
    }

    public function test_payment_denial_dispatches_event_and_notifies_student(): void
    {
        $admin = $this->activeUser('admin');
        $student = $this->activeUser('student');
        $payment = Payment::factory()->for($student)->pendingApproval()->create();

        Event::fake([PaymentDenied::class]);
        NotificationFake::fake();

        app(PaymentService::class)->deny($payment, $admin, 'Unreadable receipt');

        Event::assertDispatched(PaymentDenied::class, fn (PaymentDenied $event) => $event->paymentId === $payment->id && $event->reason === 'Unreadable receipt');
        $this->assertNotificationSentWithType($student, 'payment_denied');
    }

    public function test_clearance_update_dispatches_event_and_notifies_student(): void
    {
        $student = $this->activeUser('student');
        $dean = $this->activeUser('dean');
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'uploaded_file_path' => 'clearance-files/supporting-file.pdf',
        ]);

        Event::fake([ClearanceUpdated::class]);
        NotificationFake::fake();

        app(ClearanceService::class)->signFor($clearance, $dean, 'dean', 'Verified');

        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $clearance->id && $event->department === 'dean');
        $this->assertNotificationSentWithType($student, 'clearance_updated');
    }

    public function test_clearance_denial_dispatches_event_and_notifies_student(): void
    {
        $student = $this->activeUser('student');
        $dean = $this->activeUser('dean');
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
        ]);

        Event::fake([ClearanceUpdated::class]);
        NotificationFake::fake();

        app(ClearanceService::class)->denyFor($clearance, $dean, 'dean', 'Missing library clearance paperwork');

        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $clearance->id
            && $event->studentId === $student->id
            && $event->department === 'dean'
            && $event->action === 'denied'
            && $event->overallStatus === 'denied');
        $this->assertNotificationSentWithType($student, 'clearance_updated');
    }

    public function test_clearance_completion_dispatches_event_and_notifies_student(): void
    {
        Storage::fake('local');
        $student = $this->activeUser('student');
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'cleared',
            'president_status' => 'cleared',
            'librarian_status' => 'cleared',
            'student_affairs_status' => 'cleared',
            'alumni_status' => 'cleared',
            'guidance_status' => 'pending',
            'uploaded_file_path' => 'clearance-files/supporting-file.pdf',
        ]);

        Event::fake([ClearanceUpdated::class, ClearanceCompleted::class]);
        NotificationFake::fake();

        app(ClearanceService::class)->signFor($clearance, $this->activeUser('guidance'), 'guidance');

        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $clearance->id && $event->department === 'guidance');
        Event::assertDispatched(ClearanceCompleted::class, fn (ClearanceCompleted $event) => $event->clearanceId === $clearance->id && $event->studentId === $student->id);
        $this->assertNotificationSentWithType($student, 'clearance_updated');
        NotificationFake::assertSentTo(
            $student,
            ClearanceCompletedNotification::class,
            fn (ClearanceCompletedNotification $notification) => ($notification->toArray($student)['type'] ?? null) === 'clearance_completed',
        );
    }

    public function test_workflow_notifications_are_queued(): void
    {
        foreach ([
            WorkflowStatusNotification::class,
            ClearanceCompletedNotification::class,
            RequestCancelledNotification::class,
            RegistrationSubmittedNotification::class,
            RegistrationApprovedNotification::class,
            RegistrationRejectedNotification::class,
            BrandedResetPasswordNotification::class,
        ] as $notificationClass) {
            $this->assertContains(ShouldQueue::class, class_implements($notificationClass));
        }
    }

    public function test_current_notification_payloads_have_safe_bell_shape(): void
    {
        $student = $this->activeUser('student');
        $admin = $this->activeUser('admin');
        $documentRequest = DocumentRequest::factory()->for($student)->create();
        $clearance = Clearance::factory()->for($student)->for($documentRequest)->create();

        $cases = [
            [new ClearanceCompletedNotification($clearance), $student],
            [new RegistrationSubmittedNotification($student), $admin],
            [new RegistrationApprovedNotification, $student],
            [new RegistrationRejectedNotification('Invalid school email'), $student],
            [new RequestCancelledNotification($documentRequest, $student), $admin],
            [new WorkflowStatusNotification([
                'type' => 'payment_approved',
                'message' => 'Your payment receipt was approved.',
                'payment_id' => 10,
            ]), $student],
        ];

        foreach ($cases as [$notification, $notifiable]) {
            $payload = $notification->toArray($notifiable);

            $this->assertIsString($payload['type'] ?? null);
            $this->assertNotSame('', $payload['type']);
            $this->assertIsString($payload['title'] ?? null);
            $this->assertNotSame('', $payload['title']);
            $this->assertIsString($payload['message'] ?? null);
            $this->assertNotSame('', $payload['message']);
            $this->assertArrayHasKey('url', $payload);
            $this->assertTrue(is_string($payload['url']) || $payload['url'] === null);
            $this->assertSame($payload, $notification->toBroadcast($notifiable)->data);
        }
    }

    public function test_reset_password_notification_is_queued_and_keeps_token_out_of_array_payload(): void
    {
        $student = $this->activeUser('student');
        $notification = new BrandedResetPasswordNotification('secret-token');

        $this->assertContains(ShouldQueue::class, class_implements($notification));
        $this->assertSame(['mail'], $notification->via($student));
        $this->assertNotContains('secret-token', $notification->toArray($student));
    }

    public function test_workflow_status_notification_broadcasts_database_payload(): void
    {
        $student = $this->activeUser('student');
        $notification = new WorkflowStatusNotification([
            'type' => 'request_approved',
            'message' => 'Your request was approved.',
        ]);

        $this->assertSame(['database', 'broadcast'], $notification->via($student));
        $this->assertSame($notification->toArray($student), $notification->toBroadcast($student)->data);
    }

    public function test_workflow_status_notification_normalizes_required_bell_keys(): void
    {
        $student = $this->activeUser('student');
        $notification = new WorkflowStatusNotification([
            'type' => 123,
            'title' => ['unsafe'],
            'message' => null,
            'url' => ['not-a-url'],
            'payment_id' => 10,
            'receipt_path' => 'receipts/private.png',
        ]);

        $this->assertSame([
            'type' => '123',
            'title' => 'Workflow update',
            'message' => 'Your workflow status was updated.',
            'url' => null,
            'payment_id' => 10,
        ], $notification->toArray($student));
    }

    private function activeUser(string $role): User
    {
        $factory = ClearanceSignatories::isSignatoryRole($role)
            ? User::factory()->signatory($role)
            : User::factory()->{$role}();

        return $factory->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function assertNotificationSentWithType(User $user, string $type): void
    {
        NotificationFake::assertSentTo(
            $user,
            WorkflowStatusNotification::class,
            fn (WorkflowStatusNotification $notification) => ($notification->toArray($user)['type'] ?? null) === $type,
        );
    }
}
