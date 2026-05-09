<?php

namespace Tests\Unit\Services;

use App\Models\ClaimSlip;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\Policy\ClaimSlipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimSlipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_ready_claim_slip_for_ready_request(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create(['release_channel' => 'registrar_window_9']);
        $request = DocumentRequest::factory()->for($student)->for($documentType)->approved()->create([
            'processing_stage' => 'ready_for_pickup',
        ]);

        $slip = $this->service()->issueForRequest($request, $admin);

        $this->assertSame($request->id, $slip->document_request_id);
        $this->assertSame($student->id, $slip->user_id);
        $this->assertSame('registrar_window_9', $slip->release_channel);
        $this->assertSame('ready', $slip->state);
        $this->assertNotNull($slip->claim_date);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'claim_slip_issued',
            'user_id' => $admin->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_it_releases_claim_slip_and_completes_request(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create([
            'processing_stage' => 'ready_for_pickup',
        ]);
        $slip = $this->createSlip($request, $student);

        $released = $this->service()->markReleased(
            $slip,
            $admin,
            'Juan Dela Cruz',
            'PhilID 1234',
            true,
            'SPA',
            'Released via proxy.'
        );

        $this->assertSame('released', $released->state);
        $this->assertSame('Juan Dela Cruz', $released->claimant_name);
        $this->assertTrue($released->is_proxy_release);
        $this->assertSame('SPA', $released->authorization_type);
        $this->assertSame($admin->id, $released->released_by);
        $this->assertNotNull($released->released_at);
        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'completed',
            'processing_stage' => 'released',
        ]);
    }

    public function test_releasing_already_released_claim_slip_is_idempotent(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->completed()->create();
        $releasedAt = now()->subDay();
        $slip = $this->createSlip($request, $student, [
            'state' => 'released',
            'claimant_name' => 'Original Claimant',
            'claimant_id_reference' => 'ID-001',
            'released_by' => $admin->id,
            'released_at' => $releasedAt,
        ]);

        $released = $this->service()->markReleased($slip, $admin, 'Changed Name', 'ID-002');

        $this->assertSame('released', $released->state);
        $this->assertSame('Original Claimant', $released->claimant_name);
        $this->assertSame($releasedAt->toDateTimeString(), $released->released_at?->toDateTimeString());
    }

    public function test_it_voids_claim_slip_with_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $slip = $this->createSlip($request, $student, ['notes' => 'Initial note.']);

        $voided = $this->service()->voidSlip($slip, $admin, 'Document reprocessing required.');

        $this->assertSame('void', $voided->state);
        $this->assertStringContainsString('Initial note.', $voided->notes);
        $this->assertStringContainsString('Voided: Document reprocessing required.', $voided->notes);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'claim_slip_voided',
            'user_id' => $admin->id,
            'affected_user_id' => $student->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSlip(DocumentRequest $request, User $student, array $overrides = []): ClaimSlip
    {
        return ClaimSlip::query()->create(array_merge([
            'document_request_id' => $request->id,
            'user_id' => $student->id,
            'release_channel' => 'registrar_window_9',
            'claim_date' => now()->toDateString(),
            'state' => 'ready',
        ], $overrides));
    }

    private function service(): ClaimSlipService
    {
        return $this->app->make(ClaimSlipService::class);
    }
}
