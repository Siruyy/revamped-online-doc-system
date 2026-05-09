<?php

namespace Tests\Feature\Auth;

use App\Models\DocumentRequest;
use App\Models\PaymentProfile;
use App\Models\RequestRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_local_storage_is_not_exposed_by_framework_storage_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_requirement_upload_rejects_pdf_extension_with_invalid_mime_type(): void
    {
        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $documentRequest = DocumentRequest::factory()->for($student)->pending()->create();
        $requirement = RequestRequirement::query()->create([
            'document_request_id' => $documentRequest->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy',
            'status' => 'missing',
        ]);

        $response = $this->actingAs($student)
            ->from(route('student.requests.show', $documentRequest))
            ->post(route('student.requests.requirements.upload', [$documentRequest, $requirement]), [
                'file' => UploadedFile::fake()->create('requirement.pdf', 10, 'text/plain'),
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_sensitive_state_changing_routes_are_rate_limited(): void
    {
        foreach ([
            'admin.profile.update',
            'admin.profile.avatar',
            'admin.requests.approve',
            'admin.requests.deny',
            'admin.requests.stage',
            'admin.requests.requirements.validate',
            'admin.requests.requirements.reject',
            'admin.requests.sla.pause',
            'admin.requests.sla.resume',
            'admin.requests.release',
            'admin.requests.hd',
            'admin.releases.release',
            'admin.releases.void',
            'admin.payments.approve',
            'admin.payments.deny',
            'admin.document-types.store',
            'admin.document-types.update',
            'admin.document-types.destroy',
            'admin.announcements.store',
            'admin.announcements.update',
            'admin.announcements.destroy',
            'admin.faqs.store',
            'admin.faqs.update',
            'admin.faqs.destroy',
            'admin.settings.payment-profile.store',
            'admin.settings.payment-profile.update',
            'admin.settings.payment-profile.toggle',
            'admin.settings.payment-profile.destroy',
            'admin.settings.payment-profile.remove-qr',
            'admin.settings.payment-profile.upsert',
            'admin.notifications.mark-read',
            'admin.notifications.mark-all-read',
            'department.profile.update',
            'department.profile.avatar',
            'department.profile.signature',
            'department.clearances.sign',
            'department.clearances.deny',
            'department.notifications.mark-read',
            'department.notifications.mark-all-read',
            'superadmin.profile.update',
            'superadmin.profile.avatar',
            'superadmin.notifications.mark-read',
            'superadmin.notifications.mark-all-read',
            'superadmin.users.store',
            'superadmin.users.bulk-approve',
            'superadmin.users.bulk-destroy',
            'superadmin.users.update',
            'superadmin.users.approve',
            'superadmin.users.reject',
            'superadmin.users.suspend',
            'superadmin.users.reactivate',
            'superadmin.users.destroy',
        ] as $routeName) {
            $this->assertContains('throttle:sensitive-actions', Route::getRoutes()->getByName($routeName)?->middleware() ?? []);
        }
    }

    public function test_student_can_view_active_payment_qr_code(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $profile = $this->createPaymentProfile(isActive: true);

        $this->actingAs($student)
            ->get(route('files.payment-qr', $profile))
            ->assertOk();
    }

    public function test_student_cannot_view_inactive_payment_qr_code(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $profile = $this->createPaymentProfile(isActive: false);

        $this->actingAs($student)
            ->get(route('files.payment-qr', $profile))
            ->assertForbidden();
    }

    public function test_department_user_cannot_view_payment_qr_code(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->teacher()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $profile = $this->createPaymentProfile(isActive: true);

        $this->actingAs($teacher)
            ->get(route('files.payment-qr', $profile))
            ->assertForbidden();
    }

    private function createPaymentProfile(bool $isActive): PaymentProfile
    {
        $path = 'payment-qr/test-qr.png';
        Storage::disk('local')->put($path, 'qr-content');

        return PaymentProfile::query()->create([
            'bank_name' => 'Test Bank',
            'account_name' => 'SVCI',
            'account_number' => '1234567890',
            'qr_path' => $path,
            'instructions' => null,
            'is_active' => $isActive,
        ]);
    }
}
