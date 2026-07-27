<?php

namespace Tests\Feature\Public;

use App\Models\AcademicProgram;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use App\Support\FileUploadLimits;
use Database\Seeders\AcademicProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
        $this->assertNull($request->requester_student_id);
        $this->assertNull($request->requester_course);
        $this->assertNull($request->requester_year_level);
        $this->assertNull($request->requester_graduation_or_last_sem);
    }

    public function test_public_request_requires_requestor_details_items_and_profile_data(): void
    {
        $response = $this->from('/request-document')->post('/request-document', []);

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors([
            'requester_division',
            'requester_name',
            'requester_email',
            'requester_contact_number',
            'requester_last_term_attended',
            'requester_last_year_attended',
            'items',
            'purpose',
            'birth_date',
            'education',
            'fulfillment_method',
        ]);
    }

    public function test_college_delivery_request_stores_structured_attendance_shipping_address_and_custom_purpose(): void
    {
        Storage::fake('local');
        $documentType = DocumentType::factory()->create([
            'category' => 'Academic',
            'requirements' => [],
        ]);

        $this->post('/request-document', $this->validPayload($documentType, [
            'requester_last_term_attended' => 'second_semester',
            'requester_last_year_attended' => '2025-2026',
            'purpose' => 'Other official purpose',
            'purpose_other' => 'Professional license application',
            'fulfillment_method' => 'delivery',
            'delivery_address' => 'Purok 1, Estaka, Dipolog City',
        ]))->assertRedirect();

        $request = DocumentRequest::query()->firstOrFail();
        $this->assertSame('college', $request->requester_division);
        $this->assertSame('second_semester', $request->requester_last_term_attended);
        $this->assertSame('2025-2026', $request->requester_last_year_attended);
        $this->assertSame('Second Semester 2025-2026', $request->requester_graduation_or_last_sem);
        $this->assertSame('Other official purpose: Professional license application', $request->purpose);
        $this->assertSame('Purok 1, Estaka, Dipolog City', $request->delivery_address);
    }

    public function test_basic_education_request_uses_level_instead_of_college_program(): void
    {
        Storage::fake('local');
        $documentType = DocumentType::factory()->create([
            'code' => 'form_138',
            'category' => 'BasicEd',
            'requirements' => [],
        ]);

        $this->post('/request-document', $this->validPayload($documentType, [
            'requester_division' => 'basic_education',
            'basic_education_level' => 'junior_high',
            'academic_program_id' => null,
            'requester_year_level' => null,
            'education' => [
                'elementary' => ['school' => 'Elementary', 'address' => 'Dipolog', 'year' => '2012'],
                'junior_high' => ['school' => 'Junior High', 'address' => 'Dipolog', 'year' => '2016'],
            ],
        ]))->assertRedirect();

        $request = DocumentRequest::query()->firstOrFail();
        $this->assertSame('basic_education', $request->requester_division);
        $this->assertSame('junior_high', $request->basic_education_level);
        $this->assertSame('JHS', $request->requester_course);
        $this->assertSame('BEC', $request->academic_department_code_snapshot);
        $this->assertNull($request->academic_program_id);
    }

    public function test_structured_attendance_and_other_purpose_options_are_enforced(): void
    {
        Storage::fake('local');
        $documentType = DocumentType::factory()->create([
            'category' => 'Academic',
            'requirements' => [],
        ]);

        $this->from('/request-document')
            ->post('/request-document', $this->validPayload($documentType, [
                'requester_last_year_attended' => '2025-9999',
                'purpose' => 'Other official purpose',
                'purpose_other' => '',
            ]))
            ->assertRedirect('/request-document')
            ->assertSessionHasErrors(['requester_last_year_attended', 'purpose_other']);
    }

    public function test_request_division_must_match_selected_document_types(): void
    {
        Storage::fake('local');
        $basicEducationType = DocumentType::factory()->create([
            'category' => 'BasicEd',
            'requirements' => [],
        ]);

        $this->from('/request-document')
            ->post('/request-document', $this->validPayload($basicEducationType))
            ->assertRedirect('/request-document')
            ->assertSessionHasErrors('items');
    }

    public function test_public_request_requires_selected_document_type_requirement_files(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create([
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $response = $this->from('/request-document')->post('/request-document', $this->validPayload($documentType, [
            'requirements' => [
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 1, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors(['requirements.valid_id_photocopy_claimant']);
    }

    public function test_public_request_rejects_invalid_upload_file_types(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create([
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $response = $this->from('/request-document')->post('/request-document', $this->validPayload($documentType, [
            'requirements' => [
                'valid_id_photocopy_claimant' => UploadedFile::fake()->create('id.txt', 1, 'text/plain'),
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 1, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors([
            'requirements.valid_id_photocopy_claimant',
        ]);
    }

    public function test_public_request_submission_stores_unevaluated_request_and_private_files(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create([
            'fee' => 75,
            'default_page_count' => 2,
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $response = $this->post('/request-document', $this->validPayload($documentType));

        $documentRequest = DocumentRequest::query()->firstOrFail();
        $response->assertRedirect(route('public.requests.submitted', $documentRequest->reference_no));

        $this->assertSame(0, User::query()->count());
        $this->assertNull($documentRequest->user_id);
        $this->assertSame('public', $documentRequest->intake_mode);
        $this->assertSame('pending', $documentRequest->status);
        $this->assertSame('Public Requestor', $documentRequest->requester_name);
        $this->assertSame('requestor@example.test', $documentRequest->requester_email);
        $this->assertNull($documentRequest->requester_student_id);
        $this->assertSame('BSIT', $documentRequest->requester_course);
        $this->assertSame(3, $documentRequest->requester_year_level);
        $this->assertSame('Second Semester 2025-2026', $documentRequest->requester_graduation_or_last_sem);
        $this->assertSame(0.0, (float) $documentRequest->fee_snapshot);
        $this->assertSame('registrar_review', $documentRequest->workflow_stage);
        $this->assertSame(0, Payment::query()->count());

        $requirement = $documentRequest->requirements()->where('requirement_key', 'valid_id_photocopy_claimant')->firstOrFail();
        $this->assertSame('valid_id_photocopy_claimant', $requirement->requirement_key);
        $this->assertSame('submitted', $requirement->status);

        $this->assertStringStartsWith("request-requirements/public/{$documentRequest->id}/", $requirement->file_path);
        Storage::disk('local')->assertExists($requirement->file_path);
    }

    public function test_public_request_submission_notifies_staff_without_private_paths(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->admin()->create(['status' => 'active']);
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);
        $documentType = DocumentType::factory()->create([
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $this->post('/request-document', $this->validPayload($documentType))->assertRedirect();

        foreach ([$admin, $superadmin] as $staff) {
            Notification::assertSentTo(
                $staff,
                WorkflowStatusNotification::class,
                function (WorkflowStatusNotification $notification, array $channels) use ($staff): bool {
                    $payload = $notification->toArray($staff);

                    return $channels === ['mail', 'database', 'broadcast']
                        && ($payload['type'] ?? null) === 'request_submitted'
                        && array_key_exists('document_request_id', $payload)
                        && ! array_key_exists('receipt_path', $payload)
                        && ! array_key_exists('file_path', $payload)
                        && ! str_contains(json_encode($payload, JSON_THROW_ON_ERROR), 'payment-receipts/')
                        && ! str_contains(json_encode($payload, JSON_THROW_ON_ERROR), 'request-requirements/');
                },
            );
        }
    }

    public function test_public_request_stores_shared_requirement_file_once(): void
    {
        Storage::fake('local');

        $firstType = DocumentType::factory()->create([
            'fee' => 75,
            'default_page_count' => 1,
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);
        $secondType = DocumentType::factory()->create([
            'fee' => 50,
            'default_page_count' => 1,
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $this->post('/request-document', $this->validPayload($firstType, [
            'items' => [
                ['document_type_id' => $firstType->id, 'copies' => 1],
                ['document_type_id' => $secondType->id, 'copies' => 1],
            ],
        ]))->assertRedirect();

        $documentRequest = DocumentRequest::query()->firstOrFail();

        $this->assertSame(1, $documentRequest->requirements()->where('requirement_key', 'valid_id_photocopy_claimant')->count());
        $this->assertCount(3, Storage::disk('local')->allFiles("request-requirements/public/{$documentRequest->id}"));
    }

    public function test_admin_can_preview_public_payment_receipt_inline(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create();
        $documentRequest = DocumentRequest::query()->create([
            'user_id' => null,
            'document_type_id' => $documentType->id,
            'requester_name' => 'Public Requestor',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'SVCI-2026-0001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'status' => 'pending',
            'processing_stage' => 'not_started',
            'intake_mode' => 'public',
            'purpose' => 'For employment',
        ]);
        $receiptPath = "payment-receipts/public/{$documentRequest->id}/receipt.jpg";
        Storage::disk('local')->put($receiptPath, 'receipt-content');
        $payment = Payment::query()->create([
            'user_id' => null,
            'document_request_id' => $documentRequest->id,
            'total_amount' => 150.00,
            'receipt_path' => $receiptPath,
            'payment_method' => 'GCash',
            'reference_number' => 'GCASH-12345',
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('files.payment-receipt', $payment))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=receipt.jpg');
    }

    public function test_admin_cannot_download_public_payment_receipt_with_traversal_path(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create();
        $documentRequest = DocumentRequest::query()->create([
            'user_id' => null,
            'document_type_id' => $documentType->id,
            'requester_name' => 'Public Requestor',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'SVCI-2026-0001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'status' => 'pending',
            'processing_stage' => 'not_started',
            'intake_mode' => 'public',
            'purpose' => 'For employment',
        ]);
        Storage::disk('local')->put('secret.jpg', 'secret-content');
        $payment = Payment::query()->create([
            'user_id' => null,
            'document_request_id' => $documentRequest->id,
            'total_amount' => 150.00,
            'receipt_path' => "payment-receipts/public/{$documentRequest->id}/../../secret.jpg",
            'payment_method' => 'GCash',
            'reference_number' => 'GCASH-12345',
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('files.payment-receipt', $payment))
            ->assertNotFound();
    }

    public function test_public_request_page_uses_public_payment_qr_url(): void
    {
        Storage::fake('local');
        $path = 'payment-qr/public-qr.png';
        Storage::disk('local')->put($path, 'qr-content');
        $profile = PaymentProfile::query()->create([
            'bank_name' => 'Test Bank',
            'account_name' => 'SVCI',
            'account_number' => '1234567890',
            'qr_path' => $path,
            'instructions' => null,
            'is_active' => true,
        ]);

        $this->get('/request-document')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/RequestDocument', false)
                ->where('paymentProfile.qr_url', route('public.files.payment-qr', $profile))
            );
    }

    public function test_public_request_page_exposes_effective_upload_limits(): void
    {
        $this->get('/request-document')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/RequestDocument', false)
                ->where('uploadLimits.max_file_kb', FileUploadLimits::publicIntakeMaxFileKilobytes())
                ->where('uploadLimits.max_file_bytes', FileUploadLimits::publicIntakeMaxFileKilobytes() * 1024)
                ->where('uploadLimits.max_total_kb', FileUploadLimits::publicIntakeMaxPostKilobytes())
            );
    }

    public function test_public_request_rejects_uploads_over_effective_file_limit(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create([
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);
        $tooLargeKilobytes = FileUploadLimits::publicIntakeMaxFileKilobytes() + 1;

        $response = $this->from('/request-document')->post('/request-document', $this->validPayload($documentType, [
            'requirements' => [
                'valid_id_photocopy_claimant' => UploadedFile::fake()->create('valid-id.pdf', $tooLargeKilobytes, 'application/pdf'),
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 1, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors([
            'requirements.valid_id_photocopy_claimant',
        ]);
    }

    public function test_public_payment_qr_route_serves_only_active_profiles(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-qr/active.png', 'active-qr');
        Storage::disk('local')->put('payment-qr/inactive.png', 'inactive-qr');
        Storage::disk('local')->put('secret.png', 'secret-content');

        $activeProfile = PaymentProfile::query()->create([
            'bank_name' => 'Test Bank',
            'account_name' => 'SVCI',
            'account_number' => '1234567890',
            'qr_path' => 'payment-qr/active.png',
            'instructions' => null,
            'is_active' => true,
        ]);
        $inactiveProfile = PaymentProfile::query()->create([
            'bank_name' => 'Old Bank',
            'account_name' => 'SVCI',
            'account_number' => '0000000000',
            'qr_path' => 'payment-qr/inactive.png',
            'instructions' => null,
            'is_active' => false,
        ]);
        $traversalProfile = PaymentProfile::query()->create([
            'bank_name' => 'Test Bank',
            'account_name' => 'SVCI',
            'account_number' => '1234567890',
            'qr_path' => 'payment-qr/../secret.png',
            'instructions' => null,
            'is_active' => true,
        ]);

        $this->get(route('public.files.payment-qr', $activeProfile))->assertOk();
        $this->get(route('public.files.payment-qr', $inactiveProfile))->assertNotFound();
        $this->get(route('public.files.payment-qr', $traversalProfile))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(DocumentType $documentType, array $overrides = []): array
    {
        $this->seed(AcademicProgramSeeder::class);
        $program = AcademicProgram::query()->where('code', 'BSIT')->firstOrFail();

        return array_replace([
            'requester_name' => 'Public Requestor',
            'requester_email' => 'requestor@example.test',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => null,
            'requester_division' => 'college',
            'academic_program_id' => $program->id,
            'requester_year_level' => 3,
            'requester_last_term_attended' => 'second_semester',
            'requester_last_year_attended' => '2025-2026',
            'basic_education_level' => null,
            'birth_date' => '2000-01-01',
            'birth_place' => 'Dipolog City',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'citizenship' => 'Filipino',
            'home_address' => 'Dipolog City',
            'father_name' => 'Father',
            'mother_maiden_name' => 'Mother',
            'parents_address' => 'Dipolog City',
            'guardian_name' => 'Guardian',
            'guardian_address' => 'Dipolog City',
            'education' => [
                'elementary' => ['school' => 'Elementary', 'address' => 'Dipolog', 'year' => '2012'],
                'junior_high' => ['school' => 'Junior High', 'address' => 'Dipolog', 'year' => '2016'],
                'senior_high' => ['school' => 'Senior High', 'address' => 'Dipolog', 'year' => '2018'],
            ],
            'employment_status' => 'not_employed',
            'items' => [[
                'document_type_id' => $documentType->id,
                'copies' => 1,
            ]],
            'purpose' => 'Employment',
            'fulfillment_method' => 'pickup',
            'requirements' => [
                'valid_id_photocopy_claimant' => UploadedFile::fake()->image('valid-id.jpg'),
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 1, 'application/pdf'),
            ],
        ], $overrides);
    }
}
