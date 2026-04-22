<?php

namespace Tests\Feature\Admin;

use App\Events\ClearanceCreated;
use App\Events\PaymentDenied;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_payments(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        Payment::factory()->for($student)->pendingApproval()->create();

        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk();
    }

    public function test_admin_can_approve_payment_and_initialize_clearance(): void
    {
        Event::fake([ClearanceCreated::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->pendingApproval()->create();

        $this->actingAs($admin)->post(route('admin.payments.approve', $payment))->assertRedirect();

        Event::assertDispatched(ClearanceCreated::class);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('clearances', [
            'user_id' => $student->id,
            'document_request_id' => $request->id,
        ]);
    }

    public function test_admin_can_deny_payment_with_reason(): void
    {
        Event::fake([PaymentDenied::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $payment = Payment::factory()->for($student)->pendingApproval()->create();

        $this->actingAs($admin)->post(route('admin.payments.deny', $payment), [
            'denial_reason' => 'Receipt details mismatch',
        ])->assertRedirect();

        Event::assertDispatched(PaymentDenied::class);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'denied',
            'denial_reason' => 'Receipt details mismatch',
        ]);
    }

    public function test_admin_can_preview_payment_receipt_file(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $payment = Payment::factory()->for($student)->pendingApproval()->create([
            'receipt_path' => "payment-receipts/{$student->id}/receipt.pdf",
        ]);
        Storage::disk('local')->put($payment->receipt_path, 'pdf-content');

        $this->actingAs($admin)->get(route('files.payment-receipt', $payment))->assertOk();
    }

    private function createAdmin(): User
    {
        return User::factory()->admin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function createStudent(): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
