<?php

namespace Tests\Feature\Public;

use App\Models\ClaimSlip;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\RequestRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_form_prefills_only_valid_reference_query(): void
    {
        $this->get('/track-document?reference_no=REQ-2026-123456')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackDocument', false)
                ->where('reference', 'REQ-2026-123456')
            );

        $this->get('/track-document?reference_no='.str_repeat('A', 200))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackDocument', false)
                ->where('reference', '')
            );
    }

    public function test_valid_reference_returns_privacy_safe_tracking_result(): void
    {
        $documentType = DocumentType::factory()->create(['name' => 'Transcript of Records']);
        $request = DocumentRequest::factory()->create([
            'reference_no' => 'REQ-2026-123456',
            'user_id' => null,
            'document_type_id' => $documentType->id,
            'requester_email' => 'private@example.test',
            'requester_contact_number' => '09171234567',
            'status' => 'approved',
            'processing_stage' => 'processing',
            'expected_release_on' => now()->addDays(5)->toDateString(),
        ]);
        DocumentRequestItem::query()->create([
            'document_request_id' => $request->id,
            'document_type_id' => $documentType->id,
            'copies' => 2,
            'page_count_snapshot' => 3,
            'fee_per_page_snapshot' => 75,
            'line_total' => 450,
        ]);
        Payment::factory()->create([
            'user_id' => null,
            'document_request_id' => $request->id,
            'total_amount' => 450,
            'receipt_path' => 'payment-receipts/public/'.$request->id.'/receipt.jpg',
            'status' => 'approved',
        ]);
        RequestRequirement::query()->create([
            'document_request_id' => $request->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy of claimant',
            'status' => 'submitted',
            'file_path' => 'request-requirements/public/'.$request->id.'/valid-id.pdf',
        ]);
        Clearance::factory()->create([
            'user_id' => User::factory()->student(),
            'document_request_id' => $request->id,
            'overall_status' => 'in_progress',
        ]);

        $response = $this->from('/track-document')->post('/track-document', [
            'reference_no' => 'REQ-2026-123456',
        ]);

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackResult', false)
                ->where('reference_no', 'REQ-2026-123456')
                ->where('result.status', 'approved')
                ->where('result.processing_stage', 'processing')
                ->where('result.documents.0.name', 'Transcript of Records')
                ->where('result.documents.0.copies', 2)
                ->where('result.documents.0.line_total', '450.00')
                ->where('result.payment.status', 'approved')
                ->where('result.payment.total_amount', '450.00')
                ->where('result.clearance.overall_status', 'in_progress')
            );

        $content = $response->getContent();
        $this->assertStringNotContainsString('private@example.test', $content);
        $this->assertStringNotContainsString('09171234567', $content);
        $this->assertStringNotContainsString('receipt.jpg', $content);
        $this->assertStringNotContainsString('valid-id.pdf', $content);
        $this->assertStringNotContainsString('"id":'.$request->id, $content);
        $this->assertStringNotContainsString('"id":'.$documentType->id, $content);
        $this->assertStringNotContainsString('document_request_id', $content);
        $this->assertStringNotContainsString('requester_email', $content);
        $this->assertStringNotContainsString('requester_contact_number', $content);
        $this->assertStringNotContainsString('receipt_path', $content);
        $this->assertStringNotContainsString('file_path', $content);
    }

    public function test_unknown_reference_shows_generic_not_found_result(): void
    {
        $response = $this->from('/track-document')->post('/track-document', [
            'reference_no' => 'REQ-2026-000000',
        ]);

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackResult', false)
                ->where('reference_no', 'REQ-2026-000000')
                ->where('result', null)
                ->where('notFound', true)
            );
    }

    public function test_denied_request_includes_denial_reason(): void
    {
        DocumentRequest::factory()->create([
            'reference_no' => 'REQ-2026-654321',
            'user_id' => null,
            'status' => 'denied',
            'denial_reason' => 'Receipt is unreadable.',
        ]);

        $this->from('/track-document')->post('/track-document', [
            'reference_no' => 'REQ-2026-654321',
        ])->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackResult', false)
                ->where('result.status', 'denied')
                ->where('result.denial_reason', 'Receipt is unreadable.')
            );
    }

    public function test_tracking_handles_legacy_request_without_items_payment_or_clearance(): void
    {
        $documentType = DocumentType::factory()->create(['name' => 'Certificate of Enrollment']);
        DocumentRequest::factory()->create([
            'reference_no' => 'REQ-2026-111111',
            'user_id' => null,
            'document_type_id' => $documentType->id,
            'quantity' => 1,
            'fee_snapshot' => 120,
            'status' => 'pending',
            'processing_stage' => 'not_started',
        ]);

        $this->from('/track-document')->post('/track-document', [
            'reference_no' => 'REQ-2026-111111',
        ])->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackResult', false)
                ->where('result.documents.0.name', 'Certificate of Enrollment')
                ->where('result.documents.0.copies', 1)
                ->where('result.documents.0.line_total', '120.00')
                ->where('result.payment', null)
                ->where('result.clearance', null)
            );
    }

    public function test_claim_slip_is_only_exposed_when_ready_or_released(): void
    {
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->create([
            'reference_no' => 'REQ-2026-222222',
            'user_id' => null,
            'status' => 'completed',
            'processing_stage' => 'ready_for_pickup',
        ]);
        ClaimSlip::query()->create([
            'claim_number' => 'CLS-2026-123456',
            'document_request_id' => $request->id,
            'user_id' => $student->id,
            'release_channel' => 'pickup',
            'claim_date' => now()->addDay()->toDateString(),
            'state' => 'ready',
        ]);

        $this->from('/track-document')->post('/track-document', [
            'reference_no' => 'REQ-2026-222222',
        ])->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackResult', false)
                ->where('result.claim_slip.claim_number', 'CLS-2026-123456')
                ->where('result.claim_slip.claim_date', now()->addDay()->toDateString())
            );
    }

    public function test_tracking_lookup_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->post('/track-document', ['reference_no' => 'REQ-2026-000000'])
                ->assertOk();
        }

        $this->post('/track-document', ['reference_no' => 'REQ-2026-000000'])
            ->assertStatus(429);
    }
}
