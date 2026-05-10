<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use App\Notifications\RegistrationRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_pending_users(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        User::factory()->pending()->count(2)->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.users.pending'))
            ->assertOk();
    }

    public function test_superadmin_can_approve_pending_user(): void
    {
        Event::fake();
        Notification::fake();

        $superAdmin = User::factory()->superadmin()->create();
        $pendingStudent = User::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.approve', $pendingStudent))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $pendingStudent->id,
            'status' => 'active',
            'approved_by' => $superAdmin->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'affected_user_id' => $pendingStudent->id,
            'action' => 'registration_approved',
        ]);

        Notification::assertSentTo($pendingStudent, RegistrationApprovedNotification::class);
    }

    public function test_superadmin_can_reject_pending_user(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->superadmin()->create();
        $pendingStudent = User::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.reject', $pendingStudent), [
                'reason' => 'Incomplete student profile.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $pendingStudent->id,
            'status' => 'rejected',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'affected_user_id' => $pendingStudent->id,
            'action' => 'registration_rejected',
        ]);

        Notification::assertSentTo($pendingStudent, RegistrationRejectedNotification::class);
    }
}
