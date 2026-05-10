<?php

namespace Tests\Unit\Services;

use App\Events\ClearanceCreated;
use App\Events\PaymentApproved;
use App\Events\PaymentDenied;
use App\Events\PaymentSubmitted;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_receipt_after_request_approval(): void
    {
        Event::fake([PaymentSubmitted::class]);
        Notification::fake();
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        $updated = $this->service()->uploadReceipt(
            $payment,
            UploadedFile::fake()->create('receipt.PDF', 12, 'application/pdf'),
            'GCash',
            'REF-123'
        );

        $this->assertSame('pending_approval', $updated->status);
        $this->assertSame('GCash', $updated->payment_method);
        $this->assertSame('REF-123', $updated->reference_number);
        $this->assertStringStartsWith("payment-receipts/{$student->id}/", $updated->receipt_path);
        $this->assertStringEndsWith('.pdf', $updated->receipt_path);
        Storage::disk('local')->assertExists($updated->receipt_path);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment_submitted',
            'user_id' => $student->id,
            'affected_user_id' => $student->id,
        ]);
        Event::assertDispatched(PaymentSubmitted::class);
    }

    public function test_it_rejects_receipt_upload_before_request_approval(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('after your request has been approved');

        $this->service()->uploadReceipt(
            $payment,
            UploadedFile::fake()->image('receipt.jpg'),
            'Cash',
            null
        );
    }

    public function test_it_approves_payment_and_creates_clearance_when_required(): void
    {
        Event::fake([PaymentApproved::class, ClearanceCreated::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create(['flags' => []]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        $payment = Payment::factory()->for($student)->for($request)->pendingApproval()->create();

        $approved = $this->service()->approve($payment, $admin);

        $this->assertSame('approved', $approved->status);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertDatabaseHas('clearances', [
            'user_id' => $student->id,
            'document_request_id' => $request->id,
            'overall_status' => 'in_progress',
        ]);
        Event::assertDispatched(PaymentApproved::class);
        Event::assertDispatched(ClearanceCreated::class);
    }

    public function test_it_denies_payment_with_reason(): void
    {
        Event::fake([PaymentDenied::class]);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $payment = Payment::factory()->for($student)->pendingApproval()->create();

        $denied = $this->service()->deny($payment, $admin, 'Receipt details mismatch');

        $this->assertSame('denied', $denied->status);
        $this->assertSame('Receipt details mismatch', $denied->denial_reason);
        $this->assertSame($admin->id, $denied->approved_by);
        Event::assertDispatched(PaymentDenied::class);
    }

    public function test_it_prevents_duplicate_payment_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $payment = Payment::factory()->for($student)->approved()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only submitted payments can be approved.');

        $this->service()->approve($payment, $admin);
    }

    private function service(): PaymentService
    {
        return $this->app->make(PaymentService::class);
    }
}
