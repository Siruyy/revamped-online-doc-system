<?php

namespace Tests\Feature\Public;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_and_payment_can_be_persisted_without_user_rows(): void
    {
        $documentType = DocumentType::factory()->create();

        $request = DocumentRequest::create([
            'user_id' => null,
            'document_type_id' => $documentType->id,
            'requester_name' => 'Public Requestor',
            'requester_email' => 'requestor@example.test',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'SVCI-2026-0001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'status' => 'pending',
            'processing_stage' => 'not_started',
            'purpose' => 'For employment',
        ]);

        $payment = Payment::create([
            'user_id' => null,
            'document_request_id' => $request->id,
            'total_amount' => 150.00,
            'payment_method' => 'GCash',
            'reference_number' => 'GCASH-12345',
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        $this->assertSame(0, User::query()->count());
        $this->assertNull($request->user);
        $this->assertNull($payment->user);
        $this->assertTrue($request->payments->contains($payment));
        $this->assertSame('Public Requestor', $request->requester_name);
        $this->assertSame('requestor@example.test', $request->requester_email);
        $this->assertSame('09171234567', $request->requester_contact_number);
        $this->assertSame('SVCI-2026-0001', $request->requester_student_id);
        $this->assertSame('BSIT', $request->requester_course);
        $this->assertSame(3, $request->requester_year_level);
    }
}
