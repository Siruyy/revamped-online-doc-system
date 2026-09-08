<?php

namespace Tests\Feature\Admin;

use App\Models\DocumentRequest;
use App\Models\PaymentProfile;
use App\Models\SchoolBranding;
use App\Models\User;
use App\Services\Policy\ClaimSlipService;
use App\Services\SchoolBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_superadmin_can_upload_replace_and_remove_claim_slip_logo(): void
    {
        Storage::fake('local');

        foreach (['admin', 'superadmin'] as $role) {
            $actor = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($actor)->get(route($role.'.settings.branding.index'))->assertOk();
            $this->post(route($role.'.settings.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png')])
                ->assertSessionHasNoErrors()->assertRedirect();
            $old = SchoolBranding::findOrFail(1)->logo_path;
            Storage::disk('local')->assertExists($old);
            $this->assertStringStartsWith('data:image/png;base64,', app(SchoolBrandingService::class)->logoDataUri());
            $this->post(route($role.'.settings.branding.update'), ['logo' => UploadedFile::fake()->image('replacement.jpg')])
                ->assertSessionHasNoErrors();
            Storage::disk('local')->assertMissing($old);
            $current = SchoolBranding::findOrFail(1)->logo_path;
            $request = DocumentRequest::factory()->approved()->create(['processing_stage' => 'ready_for_pickup']);
            $slip = app(ClaimSlipService::class)->issueForRequest($request, $actor);
            $pdf = Storage::disk('local')->get($slip->pdf_path);
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertStringContainsString('/Subtype /Image', $pdf);
            $this->delete(route($role.'.settings.branding.destroy'))->assertRedirect();
            Storage::disk('local')->assertMissing($current);
            $this->assertNull(app(SchoolBrandingService::class)->logoDataUri());
        }
    }

    public function test_logo_upload_rejects_unsafe_files_and_unauthorized_users(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create());

        foreach ([UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'), UploadedFile::fake()->image('logo.php'), UploadedFile::fake()->image('huge.png', 3001, 1)] as $file) {
            $this->post(route('admin.settings.branding.update'), ['logo' => $file])->assertSessionHasErrors('logo');
        }
        $this->assertDatabaseCount('school_brandings', 0);
        $this->actingAs(User::factory()->student()->create());
        $this->get(route('admin.settings.branding.index'))->assertForbidden();
        $this->post(route('admin.settings.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png')])->assertForbidden();
        $this->delete(route('admin.settings.branding.destroy'))->assertForbidden();
    }

    public function test_both_staff_roles_manage_payment_details_and_all_active_options_reach_tracking(): void
    {
        Storage::fake('local');

        foreach (['admin', 'superadmin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']));
            $this->get(route($role.'.settings.payment-profile.index'))->assertOk();
            $payload = ['bank_name' => $role.' Bank', 'account_name' => 'SVCI', 'account_number' => '001234', 'instructions' => 'Wait for clearance.', 'is_active' => true];
            $this->post(route($role.'.settings.payment-profile.store'), $payload + ['qr_image' => UploadedFile::fake()->image('qr.png')])->assertSessionHasNoErrors();
            $profile = PaymentProfile::latest('id')->firstOrFail();
            $old = $profile->qr_path;
            $this->post(route($role.'.settings.payment-profile.update', $profile), array_merge($payload, ['_method' => 'PATCH', 'account_number' => '009876', 'qr_image' => UploadedFile::fake()->image('new-qr.png')]))->assertSessionHasNoErrors();
            Storage::disk('local')->assertMissing($old);
            Storage::disk('local')->assertExists($profile->fresh()->qr_path);
        }
        PaymentProfile::create(['bank_name' => 'Hidden Bank', 'account_name' => 'Hidden', 'account_number' => 'hidden', 'is_active' => false]);
        auth()->logout();
        $request = DocumentRequest::factory()->create();
        $this->post(route('track-document.show'), ['reference_no' => $request->reference_no])->assertOk()
            ->assertInertia(fn ($page) => $page->has('result.payment_profiles', 2)->where('result.payment_profiles.0.account_number', '009876'));
    }
}
