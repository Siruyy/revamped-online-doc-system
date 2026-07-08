<?php

namespace Tests\Feature\Admin;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_surfaces_ongoing_and_clearance_completed_requests(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $ongoing = DocumentRequest::factory()->approved()->create(['processing_stage' => 'processing']);
        $cleared = DocumentRequest::factory()->approved()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public Requestor',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'processing_stage' => 'processing',
        ]);
        Clearance::factory()->for($cleared, 'documentRequest')->completed()->create(['user_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('ongoingRequests', 2)
                ->where('ongoingRequests.0.id', $cleared->id)
                ->has('clearedForProcessing', 1)
                ->where('clearedForProcessing.0.id', $cleared->id)
                ->where('clearedForProcessing.0.requester_name', 'Public Requestor')
            );

        $this->assertDatabaseHas('document_requests', ['id' => $ongoing->id]);
    }

    public function test_superadmin_dashboard_surfaces_ongoing_and_clearance_completed_requests(): void
    {
        $superadmin = User::factory()->superadmin()->create(['status' => 'active']);
        $cleared = DocumentRequest::factory()->approved()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public Requestor',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
            'processing_stage' => 'processing',
        ]);
        Clearance::factory()->for($cleared, 'documentRequest')->completed()->create(['user_id' => null]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('SuperAdmin/Dashboard')
                ->has('ongoingRequests', 1)
                ->where('ongoingRequests.0.id', $cleared->id)
                ->has('clearedForProcessing', 1)
                ->where('clearedForProcessing.0.id', $cleared->id)
            );
    }
}
