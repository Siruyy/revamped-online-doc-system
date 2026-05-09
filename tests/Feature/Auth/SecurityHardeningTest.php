<?php

namespace Tests\Feature\Auth;

use App\Models\DocumentRequest;
use App\Models\RequestRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
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
            'admin.requests.approve',
            'admin.payments.approve',
            'department.clearances.sign',
            'superadmin.users.bulk-approve',
        ] as $routeName) {
            $this->assertContains('throttle:sensitive-actions', Route::getRoutes()->getByName($routeName)?->middleware() ?? []);
        }
    }
}
