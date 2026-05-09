<?php

namespace Tests\Unit\Policy;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\Policy\RequestRulesEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestRulesEngineTest extends TestCase
{
    use RefreshDatabase;

    private RequestRulesEngine $rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rules = app(RequestRulesEngine::class);
    }

    public function test_rules_for_returns_normalized_spec(): void
    {
        $type = DocumentType::factory()->create([
            'code' => 'tor',
            'name' => 'Transcript of Records',
            'category' => 'Academic',
            'fee' => 140,
            'fee_formula' => 'per_page',
            'processing_days' => 14,
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar'],
            'requirements' => ['valid_id_photocopy_claimant'],
            'flags' => [],
        ]);

        $spec = $this->rules->rulesFor($type);

        $this->assertSame('tor', $spec['code']);
        $this->assertSame(140.0, $spec['fee']);
        $this->assertSame('per_page', $spec['fee_formula']);
        $this->assertSame(14, $spec['sla_days']);
        $this->assertSame(['dean', 'registrar'], $spec['offices']);
    }

    public function test_compute_fee_fee_per_page_times_copies(): void
    {
        $type = DocumentType::factory()->create([
            'code' => 'tor',
            'fee' => 140,
            'fee_formula' => 'per_page',
            'default_page_count' => 5,
        ]);

        // fee × page_count × quantity
        $this->assertSame(420.0, $this->rules->computeFee($type, ['page_count' => 3, 'quantity' => 1]));
        $this->assertSame(840.0, $this->rules->computeFee($type, ['page_count' => 3, 'quantity' => 2]));
    }

    public function test_compute_fee_uses_default_page_count_when_none_specified(): void
    {
        $type = DocumentType::factory()->create([
            'code' => 'diploma',
            'fee' => 50,
            'fee_formula' => 'per_page',
            'default_page_count' => 4,
        ]);

        // Should use default_page_count = 4 when page_count not passed
        $this->assertSame(200.0, $this->rules->computeFee($type, ['quantity' => 1]));
        $this->assertSame(400.0, $this->rules->computeFee($type, ['quantity' => 2]));
    }

    public function test_eligibility_blocks_transferred_student_without_exception(): void
    {
        $student = User::factory()->student()->create([
            'academic_status' => 'transferred',
            'transferred_at' => now(),
            'transferred_to' => 'Other School',
        ]);

        $type = DocumentType::factory()->create([
            'code' => 'tor',
            'flags' => [],
        ]);

        $errors = $this->rules->validateEligibility($student, $type);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('transferred/dismissed', $errors[0]);
    }

    public function test_eligibility_allows_transferred_with_cno_and_external_notice(): void
    {
        $student = User::factory()->student()->create([
            'academic_status' => 'transferred',
            'transferred_at' => now(),
            'transferred_to' => 'Other School',
        ]);

        $type = DocumentType::factory()->create(['code' => 'tor', 'flags' => []]);

        $errors = $this->rules->validateEligibility($student, $type, [
            'has_cno' => true,
            'has_external_notice' => true,
        ]);

        $this->assertSame([], $errors);
    }

    public function test_eligibility_blocks_graduate_only_for_undergrad(): void
    {
        $student = User::factory()->student()->create(['is_graduate' => false]);
        $type = DocumentType::factory()->create([
            'code' => 'cert_car',
            'flags' => ['graduate_only'],
        ]);

        $errors = $this->rules->validateEligibility($student, $type);
        $this->assertNotEmpty($errors);
    }

    public function test_eligibility_blocks_special_class_without_checklist(): void
    {
        $student = User::factory()->student()->create();
        $type = DocumentType::factory()->create([
            'code' => 'cert_special_class',
            'flags' => ['eligibility_special_class'],
        ]);

        $errors = $this->rules->validateEligibility($student, $type);
        $this->assertNotEmpty($errors);
    }

    public function test_eligibility_passes_special_class_with_checklist(): void
    {
        $student = User::factory()->student()->create();
        $type = DocumentType::factory()->create([
            'code' => 'cert_special_class',
            'flags' => ['eligibility_special_class'],
        ]);

        $errors = $this->rules->validateEligibility($student, $type, [
            'special_class_eligibility' => ['graduating_this_term' => true],
        ]);

        $this->assertSame([], $errors);
    }

    public function test_eligibility_blocks_face_to_face_only(): void
    {
        $student = User::factory()->student()->create();
        $type = DocumentType::factory()->create([
            'code' => 'enrollment_survey',
            'flags' => ['face_to_face_only'],
        ]);

        $errors = $this->rules->validateEligibility($student, $type);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('in person', $errors[0]);
    }
}
