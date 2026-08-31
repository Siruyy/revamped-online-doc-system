<?php

namespace Tests\Feature\Public;

use App\Events\PaymentSubmitted;
use App\Models\AcademicDepartment;
use App\Models\AcademicProgram;
use App\Models\ClearanceStep;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use App\Services\PaymentService;
use App\Services\PublicRequestWorkflowService;
use Database\Seeders\AcademicProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevisedPublicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_creates_an_unevaluated_request_without_payment(): void
    {
        Storage::fake('local');
        $this->seed(AcademicProgramSeeder::class);
        $type = DocumentType::factory()->create([
            'code' => 'tor',
            'requirements' => [],
        ]);
        $program = AcademicProgram::query()->where('code', 'BSIT')->firstOrFail();

        $response = $this->post('/request-document', $this->payload($type, $program));

        $request = DocumentRequest::query()->firstOrFail();
        $response->assertRedirect(route('public.requests.submitted', $request->reference_no));
        $this->assertSame('registrar_review', $request->workflow_stage);
        $this->assertSame(0.0, (float) $request->fee_snapshot);
        $this->assertNull($request->evaluated_at);
        $this->assertSame($program->id, $request->academic_program_id);
        $this->assertSame('CSD', $request->academic_department_code_snapshot);
        $this->assertSame(0, Payment::query()->count());
        $this->assertDatabaseHas('academic_programs', [
            'code' => 'MPM',
            'name' => 'Master in Public Management',
            'major' => 'With Thesis',
        ]);
    }

    public function test_registrar_evaluation_locks_quote_and_builds_document_specific_clearance(): void
    {
        $this->seed(AcademicProgramSeeder::class);
        $type = DocumentType::factory()->create([
            'code' => 'cert_enrollment',
            'category' => 'Certification',
            'requirements' => [],
        ]);
        $program = AcademicProgram::query()->where('code', 'BSCS')->firstOrFail();
        $csd = AcademicDepartment::query()->where('code', 'CSD')->firstOrFail();
        $cesd = AcademicDepartment::query()->where('code', 'CESD')->firstOrFail();
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'registrar_review',
            'academic_program_id' => $program->id,
            'academic_department_code_snapshot' => 'CSD',
            'requester_email' => 'requestor@example.test',
        ]);
        $item = $request->items()->create([
            'document_type_id' => $type->id,
            'copies' => 2,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 0,
            'line_total' => 0,
            'semester_requested' => 'First Semester 2025-2026',
        ]);
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $csdDean = User::factory()->dean()->create([
            'academic_department_id' => $csd->id,
            'status' => 'active',
        ]);
        $otherDean = User::factory()->dean()->create([
            'academic_department_id' => $cesd->id,
            'status' => 'active',
        ]);
        $accounting = User::factory()->accounting()->create(['status' => 'active']);
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);
        Notification::fake();

        app(PublicRequestWorkflowService::class)->evaluate($request, $admin, [
            'shipping_fee' => 50,
            'items' => [[
                'id' => $item->id,
                'page_count' => 1,
                'base_amount' => 240,
                'authentication_amount' => 0,
                'documentary_stamp_amount' => 0,
            ]],
        ]);

        $request->refresh();
        $this->assertSame('clearance', $request->workflow_stage);
        $this->assertSame(370.0, (float) $request->quote_total);
        $this->assertNotNull($request->evaluated_at);
        $this->assertSame(
            ['dean', 'accounting'],
            $request->clearances()->firstOrFail()->steps()->orderBy('sequence')->pluck('office_code')->all()
        );
        $this->assertSame('CSD', ClearanceStep::query()->where('office_code', 'dean')->value('department_code'));
        $firstStep = ClearanceStep::query()->where('office_code', 'dean')->firstOrFail();

        foreach ([$csdDean, $superadmin] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                WorkflowStatusNotification::class,
                fn (WorkflowStatusNotification $notification, array $channels): bool => $channels === ['mail', 'database', 'broadcast']
                    && ($notification->toArray($recipient)['type'] ?? null) === 'clearance_step_actionable'
                    && ($notification->toArray($recipient)['clearance_step_id'] ?? null) === $firstStep->id,
            );
        }
        Notification::assertNotSentTo([$otherDean, $accounting], WorkflowStatusNotification::class);
    }

    public function test_basic_education_evaluation_routes_to_principal_then_accounting(): void
    {
        $type = DocumentType::factory()->create([
            'code' => 'form_138',
            'category' => 'BasicEd',
            'requirements' => [],
        ]);
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'registrar_review',
            'requester_division' => 'basic_education',
            'basic_education_level' => 'senior_high',
            'academic_department_code_snapshot' => 'BEC',
            'requester_email' => 'requestor@example.test',
        ]);
        $item = $request->items()->create([
            'document_type_id' => $type->id,
            'copies' => 1,
            'page_count_snapshot' => 1,
            'fee_per_page_snapshot' => 0,
            'line_total' => 0,
        ]);
        $admin = User::factory()->admin()->create();

        app(PublicRequestWorkflowService::class)->evaluate($request, $admin, [
            'shipping_fee' => 0,
            'items' => [[
                'id' => $item->id,
                'page_count' => 1,
                'base_amount' => 240,
                'authentication_amount' => 0,
                'documentary_stamp_amount' => 0,
            ]],
        ]);

        $this->assertSame(
            ['principal', 'accounting'],
            $request->clearances()->firstOrFail()->steps()->orderBy('sequence')->pluck('office_code')->all(),
        );
        $this->assertSame(
            'BEC Principal',
            $request->clearances()->firstOrFail()->steps()->where('office_code', 'principal')->value('label'),
        );
    }

    public function test_clearance_runs_in_parallel_and_payment_opens_only_after_accounting_clears(): void
    {
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'clearance',
            'quote_total' => 165,
            'fee_snapshot' => 165,
        ]);
        $clearance = $request->clearances()->create(['user_id' => null]);
        $deanStep = $clearance->steps()->create([
            'office_code' => 'dean',
            'label' => 'Program Dean',
            'sequence' => 1,
        ]);
        $accountingStep = $clearance->steps()->create([
            'office_code' => 'accounting',
            'label' => 'Accounting Office',
            'sequence' => 2,
        ]);
        $dean = User::factory()->signatory('dean')->create();
        $accounting = User::factory()->create(['role' => 'accounting', 'status' => 'active']);
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);
        $guidance = User::factory()->guidance()->create(['status' => 'active']);
        Notification::fake();

        try {
            app(PublicRequestWorkflowService::class)->signStep($accountingStep, $accounting);
            $this->fail('Accounting must not sign before the dean.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('All other clearance offices must be cleared before the Accounting Office can sign.', $exception->getMessage());
        }

        app(PublicRequestWorkflowService::class)->signStep($deanStep, $dean);

        foreach ([$accounting, $superadmin] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                WorkflowStatusNotification::class,
                fn (WorkflowStatusNotification $notification): bool => ($notification->toArray($recipient)['type'] ?? null) === 'clearance_step_actionable'
                    && ($notification->toArray($recipient)['clearance_step_id'] ?? null) === $accountingStep->id,
            );
        }
        Notification::assertNotSentTo($guidance, WorkflowStatusNotification::class);
        app(PublicRequestWorkflowService::class)->signStep($accountingStep, $accounting);

        $request->refresh();
        $this->assertSame('awaiting_payment', $request->workflow_stage);
        $this->assertDatabaseHas('payments', [
            'document_request_id' => $request->id,
            'status' => 'pending',
            'total_amount' => 165,
        ]);
    }

    public function test_admin_can_sign_the_registrar_step_for_form_137_requests(): void
    {
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'clearance',
        ]);
        $clearance = $request->clearances()->create(['user_id' => null]);
        $step = $clearance->steps()->create([
            'office_code' => 'registrar',
            'label' => 'Office of the Registrar',
            'sequence' => 1,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.requests.registrar-clearance', $request), ['remarks' => 'Records verified.'])
            ->assertRedirect();

        $this->assertSame('cleared', $step->refresh()->status);
        $this->assertSame($admin->id, $step->signed_by);
    }

    public function test_department_correction_creates_a_requestor_follow_up_upload_slot(): void
    {
        Storage::fake('local');
        $accessCode = 'SECURE1234';
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'clearance',
            'tracking_access_hash' => hash('sha256', $accessCode),
        ]);
        $clearance = $request->clearances()->create(['user_id' => null]);
        $step = $clearance->steps()->create([
            'office_code' => 'dean',
            'label' => 'Program Dean',
            'sequence' => 1,
        ]);
        $dean = User::factory()->signatory('dean')->create();
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);
        $accounting = User::factory()->accounting()->create(['status' => 'active']);
        Notification::fake();

        app(PublicRequestWorkflowService::class)->requestAction($step, $dean, 'Upload a clearer supporting document.');

        $this->assertDatabaseHas('request_requirements', [
            'document_request_id' => $request->id,
            'requirement_key' => "clearance_follow_up_{$step->id}",
            'label' => 'Program Dean follow-up document',
            'status' => 'rejected',
            'notes' => 'Upload a clearer supporting document.',
        ]);

        $requirement = $request->requirements()->where('requirement_key', "clearance_follow_up_{$step->id}")->firstOrFail();
        $route = route('public.requests.requirements.upload', [$request, $requirement]);

        $this->post($route, [
            'access_code' => 'WRONG-CODE',
            'file' => UploadedFile::fake()->create('correction.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $this->post($route, [
            'access_code' => $accessCode,
            'file' => UploadedFile::fake()->create('correction.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame('submitted', $requirement->refresh()->status);
        $this->assertSame('pending', $step->refresh()->status);
        Storage::disk('local')->assertExists($requirement->file_path);

        foreach ([$dean, $superadmin] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                WorkflowStatusNotification::class,
                fn (WorkflowStatusNotification $notification): bool => ($notification->toArray($recipient)['type'] ?? null) === 'clearance_step_resubmitted'
                    && ($notification->toArray($recipient)['clearance_step_id'] ?? null) === $step->id,
            );
        }
        Notification::assertNotSentTo($accounting, WorkflowStatusNotification::class);
    }

    public function test_public_payment_approval_notifies_registrar_staff_for_processing(): void
    {
        Notification::fake();
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'payment_review',
            'requester_email' => 'requestor@example.test',
        ]);
        $payment = Payment::factory()->for($request)->pendingApproval()->create(['user_id' => null]);
        $accounting = User::factory()->accounting()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);

        app(PaymentService::class)->approve($payment, $accounting);

        foreach ([$admin, $superadmin] as $recipient) {
            Notification::assertSentTo(
                $recipient,
                WorkflowStatusNotification::class,
                fn (WorkflowStatusNotification $notification, array $channels): bool => $channels === ['mail', 'database', 'broadcast']
                    && ($notification->toArray($recipient)['type'] ?? null) === 'payment_approved_for_processing',
            );
        }
        Notification::assertNotSentTo($accounting, WorkflowStatusNotification::class);
    }

    public function test_public_receipt_submission_notifies_accounting_instead_of_admin(): void
    {
        Storage::fake('local');
        Notification::fake();
        Event::fake([PaymentSubmitted::class]);
        $accessCode = 'PAYMENT1234';
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'awaiting_payment',
            'tracking_access_hash' => hash('sha256', $accessCode),
            'requester_name' => 'Public Payment Requestor',
        ]);
        $payment = Payment::factory()->for($request)->create([
            'user_id' => null,
            'status' => 'pending',
        ]);
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $accounting = User::factory()->accounting()->create(['status' => 'active']);
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);

        $this->post(route('public.requests.payment.upload', $request), [
            'access_code' => $accessCode,
            'payment_method' => 'gcash',
            'reference_number' => 'GCASH-123',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertRedirect();

        $this->assertSame('pending_approval', $payment->refresh()->status);
        $this->assertSame('payment_review', $request->refresh()->workflow_stage);
        Event::assertDispatched(PaymentSubmitted::class, fn (PaymentSubmitted $event): bool => $event->paymentId === $payment->id);
        Notification::assertSentTo($accounting, WorkflowStatusNotification::class);
        Notification::assertSentTo($superadmin, WorkflowStatusNotification::class);
        Notification::assertNotSentTo($admin, WorkflowStatusNotification::class);
    }

    public function test_public_receipt_requires_supported_method_and_reference_number(): void
    {
        Storage::fake('local');
        $accessCode = 'PAYMENT1234';
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'workflow_stage' => 'awaiting_payment',
            'tracking_access_hash' => hash('sha256', $accessCode),
        ]);
        Payment::factory()->for($request)->create([
            'user_id' => null,
            'status' => 'pending',
        ]);

        $this->from(route('track-document', ['reference_no' => $request->reference_no]))
            ->post(route('public.requests.payment.upload', $request), [
                'access_code' => $accessCode,
                'payment_method' => 'Cash',
                'reference_number' => '',
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertSessionHasErrors(['payment_method', 'reference_number']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DocumentType $type, AcademicProgram $program): array
    {
        return [
            'requester_name' => 'Public Requestor',
            'requester_email' => 'requestor@example.test',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => '2020-0001',
            'requester_division' => 'college',
            'academic_program_id' => $program->id,
            'requester_year_level' => 4,
            'requester_last_term_attended' => 'second_semester',
            'requester_last_year_attended' => '2025-2026',
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
                'elementary' => ['school' => 'Elementary School', 'address' => 'Dipolog', 'year' => '2012'],
                'junior_high' => ['school' => 'Junior High School', 'address' => 'Dipolog', 'year' => '2016'],
                'senior_high' => ['school' => 'Senior High School', 'address' => 'Dipolog', 'year' => '2018'],
            ],
            'employment_status' => 'not_employed',
            'purpose' => 'Employment',
            'fulfillment_method' => 'pickup',
            'items' => [[
                'document_type_id' => $type->id,
                'copies' => 1,
                'authentication_requested' => false,
                'documentary_stamp_requested' => false,
            ]],
            'requirements' => [
                'photo_2x2' => UploadedFile::fake()->image('photo.jpg'),
                'psa_birth_certificate' => UploadedFile::fake()->create('psa.pdf', 100, 'application/pdf'),
            ],
        ];
    }
}
