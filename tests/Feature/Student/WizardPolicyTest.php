<?php

namespace Tests\Feature\Student;

use App\Events\RequestSubmitted;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Policy-mapped feature coverage for the student request wizard (multi-item flow).
 *
 * Each test is tagged to a section of docs/SVCI_School_Records_Policy.md.
 */
class WizardPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** §3 — TOR with per-page fee, page count taken from type default */
    public function test_wizard_computes_per_page_fee_for_tor(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'tor',
            'name' => 'Transcript of Records',
            'fee' => 140,
            'fee_formula' => 'per_page',
            'default_page_count' => 3,
            'processing_days' => 14,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'purpose' => 'For employment',
            ])
            ->assertRedirect();

        // Parent request should have fee_snapshot = 140 * 3 pages * 1 copy = 420
        $this->assertDatabaseHas('document_requests', [
            'user_id' => $student->id,
            'document_type_id' => $type->id,
            'fee_snapshot' => 420.00,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('document_request_items', [
            'document_type_id' => $type->id,
            'copies' => 1,
            'page_count_snapshot' => 3,
            'line_total' => 420.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'total_amount' => '420.00',
            'status' => 'pending',
        ]);
    }

    /** §4 — Diploma fee: fee_per_page × pages × copies */
    public function test_wizard_computes_per_page_fee_for_diploma(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'diploma',
            'fee' => 15,
            'fee_formula' => 'per_page',
            'default_page_count' => 2,
            'processing_days' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 3]],
                'purpose' => 'For board exam',
            ])
            ->assertRedirect();

        // fee × page_count × copies = 15 × 2 × 3 = 90
        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'total_amount' => '90.00',
        ]);
    }

    /** Multi-doc: two items in one request, payment total = sum of line totals */
    public function test_wizard_creates_multi_item_request_with_correct_total(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = $this->activeStudent();
        $type1 = DocumentType::factory()->create([
            'fee' => 100, 'fee_formula' => 'flat', 'default_page_count' => 1,
            'processing_days' => 3, 'is_active' => true,
        ]);
        $type2 = DocumentType::factory()->create([
            'fee' => 50, 'fee_formula' => 'flat', 'default_page_count' => 1,
            'processing_days' => 3, 'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [
                    ['document_type_id' => $type1->id, 'copies' => 2],
                    ['document_type_id' => $type2->id, 'copies' => 1],
                ],
                'purpose' => 'For scholarship application',
            ])
            ->assertRedirect();

        // type1: 100 * 2 = 200; type2: 50 * 1 = 50; total = 250
        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'total_amount' => '250.00',
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('document_request_items', 2);
    }

    /** §16 — transferred students are blocked without CNO + notice */
    public function test_wizard_blocks_transferred_student_without_cno(): void
    {
        $student = $this->activeStudent([
            'academic_status' => 'transferred',
            'transferred_to' => 'Other School',
        ]);

        $type = DocumentType::factory()->create([
            'code' => 'tor',
            'fee' => 140,
            'fee_formula' => 'per_page',
            'default_page_count' => 1,
            'processing_days' => 14,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'purpose' => 'For transfer to new school',
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('document_requests', [
            'user_id' => $student->id,
        ]);
    }

    /** §16 — transferred students may proceed when CNO + notice are flagged */
    public function test_wizard_allows_transferred_student_with_cno_and_notice(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = $this->activeStudent([
            'academic_status' => 'transferred',
            'transferred_to' => 'Other School',
        ]);

        $type = DocumentType::factory()->create([
            'code' => 'cert_transfer_credential',
            'fee' => 100,
            'fee_formula' => 'flat',
            'default_page_count' => 1,
            'processing_days' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'has_cno' => true,
                'has_external_notice' => true,
                'purpose' => 'For transfer to new school',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('document_requests', [
            'user_id' => $student->id,
            'document_type_id' => $type->id,
            'transfer_exception_requested' => true,
            'status' => 'pending',
        ]);
    }

    /** §12.1 — Special class requires at least one eligibility criterion */
    public function test_special_class_without_checklist_is_blocked(): void
    {
        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'cert_special_class',
            'fee' => 100,
            'fee_formula' => 'flat',
            'default_page_count' => 1,
            'processing_days' => 3,
            'flags' => ['eligibility_special_class'],
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'purpose' => 'For graduation enrollment',
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('document_requests', [
            'user_id' => $student->id,
        ]);
    }

    /** §12.1 — ticking any criterion satisfies the special class check */
    public function test_special_class_with_checklist_is_allowed(): void
    {
        Event::fake([RequestSubmitted::class]);
        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'cert_special_class',
            'fee' => 100,
            'fee_formula' => 'flat',
            'default_page_count' => 1,
            'processing_days' => 3,
            'flags' => ['eligibility_special_class'],
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'special_class_eligibility' => ['graduating_this_term' => '1'],
                'purpose' => 'For graduation enrollment',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('document_requests', [
            'user_id' => $student->id,
            'document_type_id' => $type->id,
            'status' => 'pending',
        ]);
    }

    /** §10 — Enrollment survey is face-to-face only */
    public function test_enrollment_survey_is_face_to_face_only(): void
    {
        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'enrollment_survey',
            'fee' => 0,
            'fee_formula' => 'flat',
            'default_page_count' => 1,
            'processing_days' => 2,
            'flags' => ['face_to_face_only'],
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'purpose' => 'For enrollment this term',
            ])
            ->assertSessionHasErrors('items');
    }

    /** §13.2 — Requirements are seeded from policy so admins can validate them */
    public function test_wizard_seeds_policy_requirements(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'diploma_reissue_college',
            'fee' => 310,
            'fee_formula' => 'flat',
            'default_page_count' => 1,
            'processing_days' => 5,
            'requirements' => ['affidavit_of_loss', 'valid_id_photocopy_claimant'],
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'purpose' => 'For replacement diploma',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('request_requirements', [
            'requirement_key' => 'affidavit_of_loss',
            'status' => 'missing',
        ]);
        $this->assertDatabaseHas('request_requirements', [
            'requirement_key' => 'valid_id_photocopy_claimant',
            'status' => 'missing',
        ]);
    }

    /** Purpose is required — omitting it must fail validation */
    public function test_purpose_is_required_for_wizard_submission(): void
    {
        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'code' => 'cert_good_moral',
            'fee' => 50,
            'fee_formula' => 'flat',
            'default_page_count' => 1,
            'processing_days' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                // deliberately omitting 'purpose'
            ])
            ->assertSessionHasErrors('purpose');

        $this->assertDatabaseMissing('document_requests', [
            'user_id' => $student->id,
            'document_type_id' => $type->id,
        ]);
    }

    /** Items array is required — submitting without items fails */
    public function test_items_are_required_for_wizard_submission(): void
    {
        $student = $this->activeStudent();

        $this->actingAs($student)
            ->from(route('student.requests.create'))
            ->post(route('student.requests.wizard.store'), [
                'purpose' => 'For employment',
                // deliberately omitting 'items'
            ])
            ->assertSessionHasErrors('items');
    }

    /** Policy-initial: payment status is pending at submission (upload locked until admin approves) */
    public function test_payment_is_pending_at_submission(): void
    {
        Event::fake([RequestSubmitted::class]);

        $student = $this->activeStudent();
        $type = DocumentType::factory()->create([
            'fee' => 100, 'fee_formula' => 'flat', 'default_page_count' => 1,
            'processing_days' => 3, 'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('student.requests.wizard.store'), [
                'items' => [['document_type_id' => $type->id, 'copies' => 1]],
                'purpose' => 'For employment',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    private function activeStudent(array $attributes = []): User
    {
        return User::factory()->student()->create(array_merge([
            'status' => 'active',
            'email_verified_at' => now(),
        ], $attributes));
    }
}
