<?php

namespace Tests\Feature\Public;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use App\Support\FileUploadLimits;
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

    public function test_public_request_requires_requestor_details_items_and_receipt(): void
    {
        $response = $this->from('/request-document')->post('/request-document', []);

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors([
            'requester_name',
            'requester_email',
            'requester_contact_number',
            'requester_course',
            'requester_year_level',
            'requester_graduation_or_last_sem',
            'items',
            'purpose',
            'payment_method',
            'receipt',
        ]);
    }

    public function test_public_request_requires_selected_document_type_requirement_files(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create([
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $response = $this->from('/request-document')->post('/request-document', $this->validPayload($documentType, [
            'requirements' => [],
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
            'receipt' => UploadedFile::fake()->create('receipt.txt', 1, 'text/plain'),
            'requirements' => [
                'valid_id_photocopy_claimant' => UploadedFile::fake()->create('id.txt', 1, 'text/plain'),
            ],
        ]));

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors([
            'receipt',
            'requirements.valid_id_photocopy_claimant',
        ]);
    }

    public function test_public_request_submission_stores_request_payment_and_private_files(): void
    {
        Storage::fake('local');

        $documentType = DocumentType::factory()->create([
            'fee' => 75,
            'default_page_count' => 2,
            'requirements' => ['valid_id_photocopy_claimant'],
        ]);

        $response = $this->post('/request-document', $this->validPayload($documentType));

        $documentRequest = DocumentRequest::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

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
        $this->assertSame('2nd Sem 2025-2026', $documentRequest->requester_graduation_or_last_sem);
        $this->assertSame(150.0, (float) $documentRequest->fee_snapshot);

        $this->assertNull($payment->user_id);
        $this->assertSame($documentRequest->id, $payment->document_request_id);
        $this->assertSame('pending_approval', $payment->status);
        $this->assertSame('GCash', $payment->payment_method);
        $this->assertSame('GCASH-12345', $payment->reference_number);
        $this->assertSame(150.0, (float) $payment->total_amount);
        $this->assertNotNull($payment->submitted_at);

        $requirement = $documentRequest->requirements()->firstOrFail();
        $this->assertSame('valid_id_photocopy_claimant', $requirement->requirement_key);
        $this->assertSame('submitted', $requirement->status);

        $this->assertStringStartsWith("payment-receipts/public/{$documentRequest->id}/", $payment->receipt_path);
        $this->assertStringStartsWith("request-requirements/public/{$documentRequest->id}/", $requirement->file_path);
        Storage::disk('local')->assertExists($payment->receipt_path);
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

                    return $channels === ['database', 'broadcast']
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

        $this->assertSame(1, $documentRequest->requirements()->count());
        $this->assertCount(1, Storage::disk('local')->allFiles("request-requirements/public/{$documentRequest->id}"));
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
            'receipt' => UploadedFile::fake()->create('receipt.pdf', $tooLargeKilobytes, 'application/pdf'),
            'requirements' => [
                'valid_id_photocopy_claimant' => UploadedFile::fake()->create('valid-id.pdf', $tooLargeKilobytes, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect('/request-document');
        $response->assertSessionHasErrors([
            'receipt',
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
        return array_replace([
            'requester_name' => 'Public Requestor',
            'requester_email' => 'requestor@example.test',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => null,
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'requester_graduation_or_last_sem' => '2nd Sem 2025-2026',
            'items' => [[
                'document_type_id' => $documentType->id,
                'copies' => 1,
            ]],
            'purpose' => 'For employment requirements',
            'payment_method' => 'GCash',
            'payment_reference_number' => 'GCASH-12345',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'requirements' => [
                'valid_id_photocopy_claimant' => UploadedFile::fake()->image('valid-id.jpg'),
            ],
        ], $overrides);
    }
}
