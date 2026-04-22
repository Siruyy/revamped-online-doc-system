<?php

namespace Tests\Feature\Student;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use App\Events\RequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_a_request_batch(): void
    {
        Event::fake([RequestSubmitted::class]);
        $student = $this->createActiveStudent();
        $docA = DocumentType::factory()->create(['fee' => 100, 'is_active' => true]);
        $docB = DocumentType::factory()->create(['fee' => 250, 'is_active' => true]);

        $response = $this->actingAs($student)->post(route('student.requests.store'), [
            'document_ids' => [$docA->id, $docB->id],
            'purpose' => 'For internship requirements',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('document_requests', 2);
        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'total_amount' => '350.00',
            'status' => 'pending',
        ]);
    }

    public function test_student_cannot_submit_inactive_document_type(): void
    {
        $student = $this->createActiveStudent();
        $inactiveDoc = DocumentType::factory()->create(['is_active' => false]);

        $response = $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.store'), [
                'document_ids' => [$inactiveDoc->id],
            ]);

        $response->assertSessionHasErrors('document_ids');
    }

    public function test_student_cannot_submit_when_active_request_exists(): void
    {
        $student = $this->createActiveStudent();
        $existingDoc = DocumentType::factory()->create();
        DocumentRequest::factory()->for($student)->for($existingDoc)->pending()->create();

        $newDoc = DocumentType::factory()->create(['is_active' => true]);

        $response = $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.store'), [
                'document_ids' => [$newDoc->id],
            ]);

        $response->assertSessionHasErrors('document_ids');
    }

    public function test_student_can_cancel_pending_request_without_receipt(): void
    {
        $student = $this->createActiveStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $response = $this->actingAs($student)->post(route('student.requests.cancel', $request));

        $response->assertRedirect();
        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_student_cannot_cancel_when_receipt_exists(): void
    {
        $student = $this->createActiveStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        Payment::factory()->for($student)->for($request)->create([
            'receipt_path' => 'payment-receipts/'.$student->id.'/sample.pdf',
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($student)
            ->from(route('student.requests.show', $request))
            ->post(route('student.requests.cancel', $request));

        $response->assertSessionHasErrors('request');
        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    private function createActiveStudent(): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
