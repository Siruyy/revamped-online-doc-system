<?php

namespace Tests\Feature\Student;

use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentUploadTest extends TestCase
{
    use RefreshDatabase;

    /** Policy-initial: student can upload receipt after request is approved */
    public function test_student_can_upload_payment_receipt(): void
    {
        Event::fake();
        Storage::fake('local');

        $student = $this->createActiveStudent();
        // Request must be approved before receipt can be uploaded
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        $response = $this->actingAs($student)->post(route('student.payments.upload', $payment), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'payment_method' => 'GCash',
            'reference_number' => 'GCASH-12345',
        ]);

        $response->assertRedirect();
        $payment->refresh();

        $this->assertEquals('pending_approval', $payment->status);
        $this->assertNotNull($payment->receipt_path);
        Storage::disk('local')->assertExists($payment->receipt_path);
    }

    /** Policy-initial: receipt upload is blocked when request is still pending */
    public function test_student_cannot_upload_receipt_while_request_is_pending(): void
    {
        $student = $this->createActiveStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        $this->actingAs($student)->post(route('student.payments.upload', $payment), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'payment_method' => 'GCash',
        ])->assertForbidden();
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $student = $this->createActiveStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        $response = $this->actingAs($student)
            ->from(route('student.payments.index'))
            ->post(route('student.payments.upload', $payment), [
                'receipt' => UploadedFile::fake()->create('script.exe', 20),
                'payment_method' => 'Cash',
            ]);

        $response->assertSessionHasErrors('receipt');
    }

    public function test_upload_rejects_oversized_file(): void
    {
        $student = $this->createActiveStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->pending()->create();

        $response = $this->actingAs($student)
            ->from(route('student.payments.index'))
            ->post(route('student.payments.upload', $payment), [
                'receipt' => UploadedFile::fake()->create('big.pdf', 6000),
                'payment_method' => 'Cash',
            ]);

        $response->assertSessionHasErrors('receipt');
    }

    public function test_student_cannot_upload_receipt_for_approved_payment(): void
    {
        $student = $this->createActiveStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->approved()->create();

        $this->actingAs($student)->post(route('student.payments.upload', $payment), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'payment_method' => 'Cash',
        ])->assertForbidden();
    }

    public function test_receipt_preview_route_checks_policy(): void
    {
        Storage::fake('local');

        $owner = $this->createActiveStudent();
        $otherStudent = $this->createActiveStudent('other@student.test');

        $request = DocumentRequest::factory()->for($owner)->pending()->create();
        $payment = Payment::factory()->for($owner)->for($request)->create([
            'receipt_path' => 'payment-receipts/'.$owner->id.'/receipt.pdf',
            'status' => 'pending_approval',
        ]);

        Storage::disk('local')->put($payment->receipt_path, 'receipt-content');

        $this->actingAs($owner)->get(route('files.payment-receipt', $payment))->assertOk();
        $this->actingAs($otherStudent)->get(route('files.payment-receipt', $payment))->assertForbidden();
    }

    private function createActiveStudent(?string $email = null): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
            'email' => $email ?? fake()->unique()->safeEmail(),
        ]);
    }
}
