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
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_local_storage_is_not_exposed_by_framework_storage_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_reverb_allowed_origins_default_to_configured_origin_without_wildcard(): void
    {
        $origins = config('reverb.apps.apps.0.allowed_origins');

        $this->assertIsArray($origins);
        $this->assertNotContains('*', $origins);
        $this->assertNotContains('', $origins);
        $this->assertContains(config('app.url', 'http://localhost'), $origins);
    }

    public function test_reverb_allowed_origins_support_comma_separated_env_values(): void
    {
        $config = $this->loadReverbConfigWithEnv([
            'REVERB_ALLOWED_ORIGINS' => 'https://example.test, https://admin.example.test, ,*',
        ]);

        $this->assertSame([
            'https://example.test',
            'https://admin.example.test',
        ], $config['apps']['apps'][0]['allowed_origins']);
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

    public function test_department_signature_upload_rejects_jpeg(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
            ->from(route('department.profile.edit'))
            ->post(route('department.profile.signature'), [
                'signature' => UploadedFile::fake()->image('signature.jpg', 100, 50)->size(100),
            ]);

        $response->assertSessionHasErrors('signature');
        $this->assertNull($teacher->fresh()->signature_path);
    }

    public function test_department_signature_upload_rejects_oversized_png(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
            ->from(route('department.profile.edit'))
            ->post(route('department.profile.signature'), [
                'signature' => UploadedFile::fake()->image('signature.png', 100, 50)->size(1025),
            ]);

        $response->assertSessionHasErrors('signature');
        $this->assertNull($teacher->fresh()->signature_path);
    }

    public function test_department_signature_upload_accepts_valid_png(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
            ->from(route('department.profile.edit'))
            ->post(route('department.profile.signature'), [
                'signature' => UploadedFile::fake()->image('signature.png', 100, 50)->size(100),
            ]);

        $response->assertRedirect(route('department.profile.edit'));

        $teacher->refresh();
        $this->assertNotNull($teacher->signature_path);
        $this->assertTrue(Str::endsWith($teacher->signature_path, '.png'));
        Storage::disk('local')->assertExists($teacher->signature_path);
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

    public function test_admin_can_download_public_request_requirement_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $requirement = $this->createPublicRequirementFile();

        $this->actingAs($admin)
            ->get(route('files.request-requirement', $requirement))
            ->assertOk();
    }

    public function test_superadmin_can_download_public_request_requirement_file(): void
    {
        Storage::fake('local');

        $superadmin = User::factory()->superadmin()->create();
        $requirement = $this->createPublicRequirementFile();

        $this->actingAs($superadmin)
            ->get(route('files.request-requirement', $requirement))
            ->assertOk();
    }

    public function test_unrelated_student_cannot_download_request_requirement_file(): void
    {
        Storage::fake('local');

        $owner = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $documentRequest = DocumentRequest::factory()->for($owner)->create();
        $path = "request-requirements/{$owner->id}/{$documentRequest->id}/valid-id.pdf";
        Storage::disk('local')->put($path, 'private requirement');
        $requirement = RequestRequirement::query()->create([
            'document_request_id' => $documentRequest->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy',
            'status' => 'submitted',
            'file_path' => $path,
        ]);

        $this->actingAs($otherStudent)
            ->get(route('files.request-requirement', $requirement))
            ->assertForbidden();
    }

    public function test_student_cannot_download_public_request_requirement_file(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $requirement = $this->createPublicRequirementFile();

        $this->actingAs($student)
            ->get(route('files.request-requirement', $requirement))
            ->assertForbidden();
    }

    public function test_department_user_cannot_download_request_requirement_file(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->teacher()->create();
        $requirement = $this->createPublicRequirementFile();

        $this->actingAs($teacher)
            ->get(route('files.request-requirement', $requirement))
            ->assertForbidden();
    }

    public function test_request_requirement_file_route_returns_404_for_missing_or_traversal_path(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $missingRequirement = $this->createPublicRequirementFile('missing.pdf', false);
        $traversalRequirement = $this->createPublicRequirementFile('../../secret.pdf');

        $this->actingAs($admin)
            ->get(route('files.request-requirement', $missingRequirement))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('files.request-requirement', $traversalRequirement))
            ->assertNotFound();
    }

    public function test_admin_request_detail_does_not_generate_public_storage_requirement_links(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $requirement = $this->createPublicRequirementFile();

        $response = $this->actingAs($admin)->get(route('admin.requests.show', $requirement->documentRequest));

        $response->assertOk();
        $this->assertStringNotContainsString('/storage/'.$requirement->file_path, $response->getContent());
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

    private function createPublicRequirementFile(string $filename = 'valid-id.pdf', bool $storeFile = true): RequestRequirement
    {
        $documentRequest = DocumentRequest::factory()->create(['user_id' => null]);
        $path = "request-requirements/public/{$documentRequest->id}/{$filename}";

        if ($storeFile) {
            Storage::disk('local')->put($path, 'private requirement');
        }

        return RequestRequirement::query()->create([
            'document_request_id' => $documentRequest->id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy',
            'status' => 'submitted',
            'file_path' => $path,
        ]);
    }

    /**
     * @param  array<string, string>  $env
     * @return array<string, mixed>
     */
    private function loadReverbConfigWithEnv(array $env): array
    {
        $previousEnv = $_ENV;
        $previousServer = $_SERVER;

        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }

        try {
            return require base_path('config/reverb.php');
        } finally {
            foreach (array_keys($env) as $key) {
                putenv($key);
            }

            $_ENV = $previousEnv;
            $_SERVER = $previousServer;
        }
    }
}
