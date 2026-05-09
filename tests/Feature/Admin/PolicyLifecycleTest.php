<?php

namespace Tests\Feature\Admin;

use App\Models\ClaimSlip;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\RequestRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Policy-mapped lifecycle coverage for admin workflows.
 * Each test cites the registrar policy section it enforces.
 */
class PolicyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /** §13.2 — admin can validate a submitted requirement */
    public function test_admin_can_validate_requirement(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $requirement = RequestRequirement::query()->create([
            'document_request_id' => $request->id,
            'requirement_key' => 'affidavit_of_loss',
            'label' => 'Affidavit of Loss',
            'file_path' => 'fake.pdf',
            'status' => 'submitted',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.requirements.validate', [$request, $requirement]))
            ->assertRedirect();

        $this->assertDatabaseHas('request_requirements', [
            'id' => $requirement->id,
            'status' => 'validated',
            'validated_by' => $admin->id,
        ]);
    }

    /** §13.2 — admin can reject a requirement with notes */
    public function test_admin_can_reject_requirement_with_notes(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $requirement = RequestRequirement::query()->create([
            'document_request_id' => $request->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID Photocopy',
            'file_path' => 'fake.pdf',
            'status' => 'submitted',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.requirements.reject', [$request, $requirement]), [
                'notes' => 'Image is blurred, please resubmit.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('request_requirements', [
            'id' => $requirement->id,
            'status' => 'rejected',
            'notes' => 'Image is blurred, please resubmit.',
        ]);
    }

    /** §13.1 — approving a non-HD request starts the SLA clock (policy-initial: no payment gate) */
    public function test_approve_non_hd_request_starts_sla_clock(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $type = DocumentType::factory()->create(['processing_days' => 3]);
        $request = DocumentRequest::factory()->for($student)->for($type)->pending()->create([
            'requires_hd_return' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $request))
            ->assertRedirect();

        $updated = $request->refresh();
        $this->assertSame('approved', $updated->status);
        $this->assertNotNull($updated->sla_start_at);
        $this->assertNotNull($updated->expected_release_on);
    }

    /** §3.2 — TOR-for-transfer holds the SLA clock until HD returns (policy-initial: no payment gate) */
    public function test_approve_hd_required_request_defers_sla_clock(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $type = DocumentType::factory()->create(['processing_days' => 14]);
        $request = DocumentRequest::factory()->for($student)->for($type)->pending()->create([
            'requires_hd_return' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $request))
            ->assertRedirect();

        $updated = $request->refresh();
        $this->assertSame('approved', $updated->status);
        $this->assertNull($updated->sla_start_at);
        $this->assertNull($updated->expected_release_on);
    }

    /** §3.2 — Marking HD received starts the 14-day clock */
    public function test_marking_hd_received_starts_clock(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $type = DocumentType::factory()->create(['processing_days' => 14]);
        $request = DocumentRequest::factory()->for($student)->for($type)->approved()->create([
            'requires_hd_return' => true,
            'sla_start_at' => null,
            'expected_release_on' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.hd', $request))
            ->assertRedirect();

        $updated = $request->refresh();
        $this->assertNotNull($updated->hd_received_at);
        $this->assertNotNull($updated->sla_start_at);
        $this->assertNotNull($updated->expected_release_on);
    }

    /** §13.2 — admin can pause the SLA with a valid reason */
    public function test_admin_can_pause_and_resume_sla(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->approved()->create([
            'expected_release_on' => now()->addDays(5)->toDateString(),
            'sla_start_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.sla.pause', $request), [
                'reason' => 'enrollment_period',
            ])
            ->assertRedirect();

        $paused = $request->refresh();
        $this->assertNotNull($paused->sla_paused_at);
        $this->assertSame('enrollment_period', $paused->sla_pause_reason);

        $this->actingAs($admin)
            ->post(route('admin.requests.sla.resume', $request))
            ->assertRedirect();

        $resumed = $request->refresh();
        $this->assertNull($resumed->sla_paused_at);
        $this->assertNull($resumed->sla_pause_reason);
        $this->assertNotNull($resumed->sla_resumed_at);
    }

    /** §13.2 — pausing with an unknown reason is rejected */
    public function test_pause_sla_with_invalid_reason_is_rejected(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->approved()->create();

        $this->actingAs($admin)
            ->from(route('admin.requests.show', $request))
            ->post(route('admin.requests.sla.pause', $request), [
                'reason' => 'whatever_I_want',
            ])
            ->assertSessionHasErrors('reason');
    }

    /** §15.3 — Stage=ready_for_pickup issues a claim slip */
    public function test_moving_to_ready_for_pickup_issues_claim_slip(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $type = DocumentType::factory()->create(['release_channel' => 'registrar_window_9']);
        $request = DocumentRequest::factory()->for($student)->for($type)->approved()->create();

        $this->actingAs($admin)
            ->post(route('admin.requests.stage', $request), [
                'processing_stage' => 'ready_for_pickup',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('claim_slips', [
            'document_request_id' => $request->id,
            'state' => 'ready',
            'release_channel' => 'registrar_window_9',
        ]);
    }

    /** §15.4 — releasing requires claimant details and records proxy info */
    public function test_release_records_claimant_details(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->approved()->create([
            'processing_stage' => 'ready_for_pickup',
        ]);
        $slip = ClaimSlip::query()->create([
            'document_request_id' => $request->id,
            'user_id' => $student->id,
            'release_channel' => 'registrar_window_9',
            'claim_date' => now()->toDateString(),
            'state' => 'ready',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.releases.release', $slip), [
                'claimant_name' => 'Juan Dela Cruz',
                'claimant_id_reference' => 'PhilID 1234-5678',
                'is_proxy_release' => true,
                'authorization_type' => 'SPA',
                'notes' => 'Released via proxy.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('claim_slips', [
            'id' => $slip->id,
            'state' => 'released',
            'claimant_name' => 'Juan Dela Cruz',
            'is_proxy_release' => true,
            'authorization_type' => 'SPA',
        ]);

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'completed',
            'processing_stage' => 'released',
        ]);
    }

    /** §15.4 — proxy release without authorization type is rejected */
    public function test_proxy_release_without_authorization_is_rejected(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $slip = ClaimSlip::query()->create([
            'document_request_id' => $request->id,
            'user_id' => $student->id,
            'release_channel' => 'registrar_window_9',
            'claim_date' => now()->toDateString(),
            'state' => 'ready',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.releases.index'))
            ->post(route('admin.releases.release', $slip), [
                'claimant_name' => 'Some Proxy',
                'claimant_id_reference' => 'ID-001',
                'is_proxy_release' => true,
            ])
            ->assertSessionHasErrors('authorization_type');
    }

    /** §15.5 — admin can void an unused claim slip with a reason */
    public function test_admin_can_void_claim_slip(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $slip = ClaimSlip::query()->create([
            'document_request_id' => $request->id,
            'user_id' => $student->id,
            'release_channel' => 'registrar_window_9',
            'claim_date' => now()->toDateString(),
            'state' => 'ready',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.releases.void', $slip), [
                'reason' => 'Document reprocessing required.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('claim_slips', [
            'id' => $slip->id,
            'state' => 'void',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function student(): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
