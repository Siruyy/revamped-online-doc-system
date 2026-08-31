<?php

namespace Tests\Feature\Public;

use App\Models\AcademicProgram;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\PaymentProfile;
use App\Models\RequestFeedback;
use App\Models\User;
use App\Services\PublicRequestWorkflowService;
use Database\Seeders\AcademicProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_intake_stores_split_name_year_status_and_proxy_details(): void
    {
        Storage::fake('local');
        $documentType = DocumentType::factory()->create(['requirements' => []]);

        $response = $this->post('/request-document', $this->payload($documentType, [
            'requester_name' => null,
            'requester_last_name' => 'Dela Cruz',
            'requester_first_name' => 'Juan',
            'requester_middle_name' => 'Santos',
            'requester_suffix' => 'Jr.',
            'requester_year_level' => null,
            'requester_year_level_status' => 'graduated',
            'is_proxy_request' => true,
            'requester_claimant_name' => 'Maria Dela Cruz',
            'representative_relationship' => 'Mother',
            'owner_residence' => 'within_country',
            'requirements' => [
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 1, 'application/pdf'),
                'authorization_letter' => UploadedFile::fake()->create('authorization.pdf', 1, 'application/pdf'),
                'spa' => UploadedFile::fake()->create('spa.pdf', 1, 'application/pdf'),
                'valid_id_photocopy_owner' => UploadedFile::fake()->create('owner-id.pdf', 1, 'application/pdf'),
                'valid_id_photocopy_claimant' => UploadedFile::fake()->create('claimant-id.pdf', 1, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect();
        $request = DocumentRequest::query()->firstOrFail();

        $this->assertSame('Dela Cruz, Juan Santos Jr.', $request->requester_name);
        $this->assertSame('Dela Cruz', $request->requester_last_name);
        $this->assertSame('Juan', $request->requester_first_name);
        $this->assertSame('Santos', $request->requester_middle_name);
        $this->assertSame('Jr.', $request->requester_suffix);
        $this->assertSame('graduated', $request->requester_year_level_status);
        $this->assertSame('Maria Dela Cruz', $request->requester_claimant_name);
        $this->assertSame('Mother', $request->representative_relationship);
        $this->assertSame('within_country', $request->owner_residence);
    }

    public function test_public_evaluation_automatically_applies_stamps_per_copy_except_exempt_documents(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $tor = DocumentType::factory()->create(['code' => 'tor', 'name' => 'Transcript of Records', 'fee' => 100, 'default_page_count' => 1, 'requirements' => []]);
        $diploma = DocumentType::factory()->create(['code' => 'diploma', 'name' => 'Diploma', 'fee' => 100, 'default_page_count' => 1, 'requirements' => []]);
        $request = $this->makePublicRequest($tor, [
            'items' => [
                ['document_type_id' => $tor->id, 'copies' => 2],
                ['document_type_id' => $diploma->id, 'copies' => 1],
            ],
        ]);

        app(PublicRequestWorkflowService::class)->evaluate($request, $admin, [
            'items' => [
                ['id' => $request->items()->where('document_type_id', $tor->id)->value('id'), 'page_count' => 1, 'base_amount' => 200, 'documentary_stamp_amount' => 0],
                ['id' => $request->items()->where('document_type_id', $diploma->id)->value('id'), 'page_count' => 1, 'base_amount' => 100, 'documentary_stamp_amount' => 999],
            ],
        ]);

        $request->refresh();
        $this->assertSame(380.0, (float) $request->quote_total);
        $this->assertSame(80.0, (float) $request->items()->where('document_type_id', $tor->id)->value('documentary_stamp_amount'));
        $this->assertSame(0.0, (float) $request->items()->where('document_type_id', $diploma->id)->value('documentary_stamp_amount'));
    }

    public function test_clearance_opens_all_non_accounting_offices_but_keeps_accounting_last(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $dean = User::factory()->dean()->create();
        $president = User::factory()->president()->create();
        $accounting = User::factory()->accounting()->create();
        $type = DocumentType::factory()->create(['code' => 'tor', 'category' => 'Academic', 'requirements' => []]);
        $request = $this->makePublicRequest($type);

        app(PublicRequestWorkflowService::class)->evaluate($request, $admin, [
            'items' => [['id' => $request->items()->value('id'), 'page_count' => 1, 'base_amount' => 100]],
        ]);

        $clearance = $request->clearances()->firstOrFail();
        $this->assertNotEmpty($clearance->steps()->where('office_code', 'dean')->first());
        $this->assertSame('pending', $clearance->steps()->where('office_code', 'accounting')->value('status'));

        $deanStep = $clearance->steps()->where('office_code', 'dean')->firstOrFail();
        app(PublicRequestWorkflowService::class)->signStep($deanStep, $dean);
        $this->assertSame('cleared', $deanStep->refresh()->status);

        $accountingStep = $clearance->steps()->where('office_code', 'accounting')->firstOrFail();
        $this->expectExceptionMessage('All other clearance offices');
        app(PublicRequestWorkflowService::class)->signStep($accountingStep, $accounting);
    }

    public function test_released_public_request_accepts_one_feedback_submission(): void
    {
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'status' => 'completed',
            'processing_stage' => 'released',
            'tracking_access_hash' => hash('sha256', 'ACCESS123'),
        ]);

        $this->post(route('public.requests.feedback.store', $request->reference_no), [
            'access_code' => 'ACCESS123',
            'rating' => 5,
            'service_rating' => 4,
            'comments' => 'Fast and clear.',
            'suggestions' => 'Keep it up.',
        ])->assertRedirect();

        $this->assertDatabaseHas('request_feedback', [
            'document_request_id' => $request->id,
            'rating' => 5,
            'service_rating' => 4,
        ]);
        $this->assertSame(1, RequestFeedback::query()->where('document_request_id', $request->id)->count());
    }

    public function test_tracking_includes_itemized_payment_breakdown_and_payment_profile(): void
    {
        Storage::fake('local');
        $type = DocumentType::factory()->create(['name' => 'Transcript of Records']);
        $request = $this->makePublicRequest($type, [
            'workflow_stage' => 'awaiting_payment',
            'quote_total' => 300,
            'shipping_fee' => 50,
        ]);
        $request->items()->update([
            'base_amount' => 200,
            'authentication_amount' => 20,
            'documentary_stamp_amount' => 40,
            'line_total' => 260,
        ]);
        PaymentProfile::query()->create([
            'bank_name' => 'Test Bank',
            'account_name' => 'SVCI',
            'account_number' => '123',
            'instructions' => 'Use your reference number.',
            'is_active' => true,
        ]);

        $this->post('/track-document', ['reference_no' => $request->reference_no])
            ->assertInertia(fn ($page) => $page
                ->where('result.quote.grand_total', '300.00')
                ->where('result.documents.0.base_amount', '200.00')
                ->where('result.documents.0.documentary_stamp_amount', '40.00')
                ->where('result.payment_profile.account_number', '123'));
    }

    /** @param array<string, mixed> $overrides */
    private function makePublicRequest(DocumentType $type, array $overrides = []): DocumentRequest
    {
        $items = $overrides['items'] ?? null;
        unset($overrides['items']);
        $request = DocumentRequest::factory()->create(array_merge([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public Requestor',
            'requester_email' => 'requestor@example.test',
            'requester_division' => 'college',
            'academic_program_id' => null,
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'workflow_stage' => 'registrar_review',
        ], $overrides, ['document_type_id' => $type->id]));

        foreach ($items ?? [['document_type_id' => $type->id, 'copies' => 1]] as $item) {
            $request->items()->create(['document_type_id' => $item['document_type_id'], 'copies' => $item['copies'], 'page_count_snapshot' => 1, 'fee_per_page_snapshot' => 0, 'line_total' => 0]);
        }

        return $request->refresh();
    }

    /** @param array<string, mixed> $overrides */
    private function payload(DocumentType $type, array $overrides = []): array
    {
        $this->seed(AcademicProgramSeeder::class);
        $program = AcademicProgram::query()->where('code', 'BSIT')->firstOrFail();

        return array_replace([
            'requester_name' => 'Public Requestor',
            'requester_last_name' => null,
            'requester_first_name' => null,
            'requester_middle_name' => null,
            'requester_suffix' => null,
            'requester_email' => 'requestor@example.test',
            'requester_contact_number' => '09171234567',
            'requester_division' => 'college',
            'academic_program_id' => $program->id,
            'requester_year_level' => 3,
            'requester_year_level_status' => null,
            'requester_last_term_attended' => 'second_semester',
            'requester_last_year_attended' => '2025-2026',
            'birth_date' => '2000-01-01',
            'birth_place' => 'Dipolog City',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'citizenship' => 'Filipino',
            'home_address' => 'Dipolog City',
            'education' => [
                'elementary' => ['school' => 'Elementary', 'address' => 'Dipolog', 'year' => '2012'],
                'junior_high' => ['school' => 'Junior High', 'address' => 'Dipolog', 'year' => '2016'],
                'senior_high' => ['school' => 'Senior High', 'address' => 'Dipolog', 'year' => '2018'],
            ],
            'employment_status' => 'not_employed',
            'items' => [['document_type_id' => $type->id, 'copies' => 1]],
            'purpose' => 'Employment',
            'fulfillment_method' => 'pickup',
            'is_proxy_request' => false,
            'requirements' => [
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 1, 'application/pdf'),
            ],
        ], $overrides);
    }
}
