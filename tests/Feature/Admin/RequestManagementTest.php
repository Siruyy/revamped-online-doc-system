<?php

namespace Tests\Feature\Admin;

use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_requests_index_and_detail(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->for(DocumentType::factory())->create();

        $this->actingAs($admin)->get(route('admin.requests.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.requests.show', $request))->assertOk();
    }

    public function test_admin_can_approve_request(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($admin)->post(route('admin.requests.approve', $request))->assertRedirect();

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_deny_request_with_reason(): void
    {
        Event::fake([RequestDenied::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($admin)->post(route('admin.requests.deny', $request), [
            'denial_reason' => 'Invalid student records',
        ])->assertRedirect();

        Event::assertDispatched(RequestDenied::class);

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'status' => 'denied',
            'denial_reason' => 'Invalid student records',
        ]);
    }

    public function test_admin_can_update_request_stage(): void
    {
        Event::fake([RequestStageUpdated::class]);

        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->approved()->create();

        $this->actingAs($admin)->post(route('admin.requests.stage', $request), [
            'processing_stage' => 'ready_for_pickup',
        ])->assertRedirect();

        Event::assertDispatched(RequestStageUpdated::class);

        $this->assertDatabaseHas('document_requests', [
            'id' => $request->id,
            'processing_stage' => 'ready_for_pickup',
        ]);
    }

    public function test_non_admin_cannot_access_admin_request_routes(): void
    {
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->pending()->create();

        $this->actingAs($student)->get(route('admin.requests.index'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.requests.approve', $request))->assertForbidden();
    }

    private function createAdmin(): User
    {
        return User::factory()->admin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function createStudent(): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
