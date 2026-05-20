<?php

namespace Database\Seeders;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DocumentTypeSeeder::class);

        $password = 'password';

        $superAdmin = $this->user('e2e.superadmin@example.com', 'E2E SuperAdmin', 'superadmin', $password);
        $admin = $this->user('e2e.admin@example.com', 'E2E Admin', 'admin', $password);
        $this->user('e2e.teacher@example.com', 'E2E Teacher', 'teacher', $password);
        $this->user('e2e.dean@example.com', 'E2E Dean', 'dean', $password);
        $this->user('e2e.accounting@example.com', 'E2E Accounting', 'accounting', $password);
        $this->user('e2e.sao@example.com', 'E2E SAO', 'sao', $password);

        $student = $this->student('e2e.student@example.com', 'E2E Student', 'E2E-STUDENT', $password, $superAdmin);
        $paymentStudent = $this->student('e2e.payment.student@example.com', 'E2E Payment Student', 'E2E-PAYMENT', $password, $superAdmin);
        $adminStudent = $this->student('e2e.adminflow.student@example.com', 'E2E Admin Flow Student', 'E2E-ADMIN', $password, $superAdmin);

        PaymentProfile::query()->updateOrCreate(
            ['bank_name' => 'E2E Test Bank'],
            [
                'account_name' => 'SVCI E2E Account',
                'account_number' => '000-111-222',
                'instructions' => 'E2E-only payment profile.',
                'is_active' => true,
            ]
        );

        $documentType = DocumentType::query()->where('code', 'special_order')->firstOrFail();

        $this->approvedPaymentFixture($paymentStudent, $admin, $documentType);
        $this->adminWorkflowFixture($adminStudent, $admin, $documentType);

        $student->touch();
    }

    private function user(string $email, string $name, string $role, string $password): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'fullname' => $name,
                'password' => $password,
                'role' => $role,
                'status' => 'active',
                'email_verified_at' => now(),
                'course' => null,
                'year_level' => null,
                'student_id' => null,
                'contact_number' => '09990000000',
            ]
        );
    }

    private function student(string $email, string $name, string $studentId, string $password, User $approver): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'fullname' => $name,
                'password' => $password,
                'role' => 'student',
                'status' => 'active',
                'email_verified_at' => now(),
                'course' => 'BSIT',
                'year_level' => 4,
                'student_id' => $studentId,
                'contact_number' => '09990000000',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]
        );
    }

    private function approvedPaymentFixture(User $student, User $admin, DocumentType $documentType): void
    {
        $request = $this->requestFor($student, $documentType, [
            'reference_no' => 'E2E-PAYMENT-REQ',
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'sla_start_at' => now(),
            'expected_release_on' => now()->addDays(3),
        ]);

        Payment::query()->updateOrCreate(
            ['document_request_id' => $request->id],
            [
                'user_id' => $student->id,
                'total_amount' => $request->fee_snapshot,
                'status' => 'pending',
                'receipt_path' => null,
                'payment_method' => null,
                'reference_number' => null,
            ]
        );
    }

    private function adminWorkflowFixture(User $student, User $admin, DocumentType $documentType): void
    {
        $pendingRequest = $this->requestFor($student, $documentType, [
            'reference_no' => 'E2E-ADMIN-PENDING',
            'status' => 'pending',
            'processing_stage' => 'not_started',
        ]);

        Payment::query()->updateOrCreate(
            ['document_request_id' => $pendingRequest->id],
            [
                'user_id' => $student->id,
                'total_amount' => $pendingRequest->fee_snapshot,
                'status' => 'pending',
            ]
        );

        $clearanceRequest = $this->requestFor($student, $documentType, [
            'reference_no' => 'E2E-CLEARANCE-REQ',
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        Storage::disk('local')->put('e2e/receipt.txt', 'E2E receipt fixture');
        Storage::disk('local')->put('e2e/clearance-supporting.txt', 'E2E clearance supporting fixture');

        Payment::query()->updateOrCreate(
            ['document_request_id' => $clearanceRequest->id],
            [
                'user_id' => $student->id,
                'total_amount' => $clearanceRequest->fee_snapshot,
                'status' => 'pending_approval',
                'receipt_path' => 'e2e/receipt.txt',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'E2E-CLEARANCE-PAYMENT',
                'submitted_at' => now(),
            ]
        );

        Clearance::query()->updateOrCreate(
            ['document_request_id' => $clearanceRequest->id],
            [
                'user_id' => $student->id,
                'teacher_status' => 'pending',
                'dean_status' => 'pending',
                'accounting_status' => 'pending',
                'sao_status' => 'pending',
                'overall_status' => 'in_progress',
                'uploaded_file_path' => 'e2e/clearance-supporting.txt',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function requestFor(User $student, DocumentType $documentType, array $attributes): DocumentRequest
    {
        $lineTotal = (float) $documentType->fee * max(1, (int) $documentType->default_page_count);

        $request = DocumentRequest::query()->updateOrCreate(
            ['reference_no' => $attributes['reference_no']],
            array_merge([
                'user_id' => $student->id,
                'document_type_id' => $documentType->id,
                'quantity' => 1,
                'page_count' => $documentType->default_page_count,
                'fee_snapshot' => $lineTotal,
                'purpose' => 'E2E happy path coverage',
            ], $attributes)
        );

        DocumentRequestItem::query()->updateOrCreate(
            ['document_request_id' => $request->id],
            [
                'document_type_id' => $documentType->id,
                'copies' => 1,
                'page_count_snapshot' => $documentType->default_page_count,
                'fee_per_page_snapshot' => $documentType->fee,
                'line_total' => $lineTotal,
            ]
        );

        return $request;
    }
}
