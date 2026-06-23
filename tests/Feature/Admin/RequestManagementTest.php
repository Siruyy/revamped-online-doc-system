<?php

namespace Tests\Feature\Admin;

use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_requests_index_and_detail(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->for(DocumentType::factory())->create();

        $this->actingAs($admin)->get(route('admin.requests.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.requests.show', $request))->assertOk();
    }

    /** Policy-initial: admin approves request directly (no payment required yet) */
    public function test_admin_can_approve_request(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($admin)->post(route('admin.requests.approve', $request))->assertRedirect();

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
        ]);
    }

    /** Policy-initial: admin can deny request without a payment receipt existing */
    public function test_admin_can_deny_request_with_reason(): void
    {
        Event::fake([RequestDenied::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($admin)->post(route('admin.requests.deny', $request), [
            'denial_reason' => 'Invalid student records',
        ])->assertRedirect();

        Event::assertDispatched(RequestDenied::class);

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'denied',
            'denial_reason' => 'Invalid student records',
        ]);
    }

    public function test_admin_denial_reason_strips_html_tags(): void
    {
        Event::fake([RequestDenied::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($admin)->post(route('admin.requests.deny', $request), [
            'denial_reason' => '<strong>Invalid</strong> student records',
        ])->assertRedirect();

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'denied',
            'denial_reason' => 'Invalid student records',
        ]);
    }

    public function test_admin_can_update_request_stage(): void
    {
        Event::fake([RequestStageUpdated::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $documentType = DocumentType::factory()->create(['flags' => ['no_clearance_needed']]);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create();
        Payment::factory()->for($student)->for($request)->approved()->create();

        $this->actingAs($admin)->post(route('admin.requests.stage', $request), [
            'processing_stage' => 'ready_for_pickup',
        ])->assertRedirect();

        Event::assertDispatched(RequestStageUpdated::class);

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'processing_stage' => 'ready_for_pickup',
        ]);
    }

    /** Policy-initial: payment receipt upload is locked when request is still pending */
    public function test_student_cannot_upload_receipt_before_request_is_approved(): void
    {
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $payment = Payment::factory()->for($student)->for($request)->create(['status' => 'pending', 'total_amount' => 100]);

        // Policy-initial: the policy gate returns 403 when the request is still pending.
        $this->actingAs($student)->post(route('student.payments.upload', $payment), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'payment_method' => 'gcash',
        ])->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_non_admin_cannot_access_admin_request_routes(): void
    {
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($student)->get(route('admin.requests.index'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.requests.approve', $request))->assertForbidden();
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
