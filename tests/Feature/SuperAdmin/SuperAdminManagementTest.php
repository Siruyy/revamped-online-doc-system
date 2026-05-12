<?php

namespace Tests\Feature\SuperAdmin;

use App\Events\RegistrationApproved;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SuperAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_dashboard_loads_with_stats(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('SuperAdmin/Dashboard')
                ->has('userCountsByRole')
                ->has('recentActivity'));
    }

    public function test_student_cannot_access_superadmin_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('superadmin.dashboard'))
            ->assertForbidden();
    }

    public function test_unverified_superadmin_cannot_access_superadmin_dashboard(): void
    {
        $superAdmin = User::factory()->superadmin()->unverified()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_superadmin_can_list_users_and_filter(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        User::factory()->student()->count(2)->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.users.index', ['role' => 'student']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('SuperAdmin/Users/Index'));
    }

    public function test_superadmin_can_create_staff(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.store'), [
                'fullname' => 'New Admin',
                'email' => 'newadmin@example.test',
                'role' => 'admin',
            ])
            ->assertRedirect(route('superadmin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.test',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_view_activity_logs(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        ActivityLog::create([
            'user_id' => $superAdmin->id,
            'affected_user_id' => null,
            'action' => 'test_action',
            'description' => 'Test log row',
            'metadata' => null,
            'created_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('SuperAdmin/Logs/Index'));
    }

    public function test_superadmin_can_view_reports(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('SuperAdmin/Reports/Index'));
    }

    public function test_admin_cannot_access_superadmin_user_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.users.index'))
            ->assertForbidden();
    }

    public function test_bulk_destroy_requires_delete_confirmation(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.bulk-destroy'), [
                'user_ids' => [$other->id],
                'confirmation' => 'NOPE',
            ])
            ->assertSessionHasErrors('confirmation');
    }

    public function test_superadmin_cannot_suspend_self(): void
    {
        $only = User::factory()->superadmin()->create();

        $this->actingAs($only)
            ->post(route('superadmin.users.suspend', $only))
            ->assertForbidden();
    }

    public function test_cannot_demote_last_active_superadmin(): void
    {
        $only = User::factory()->superadmin()->create();

        $this->actingAs($only)
            ->patch(route('superadmin.users.update', $only), [
                'fullname' => $only->fullname,
                'email' => $only->email,
                'role' => 'admin',
                'status' => 'active',
                'course' => null,
                'year_level' => null,
                'student_id' => null,
                'contact_number' => null,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_superadmin_can_suspend_peer_when_another_superadmin_exists(): void
    {
        $a = User::factory()->superadmin()->create(['email' => 'sa1@example.test']);
        $b = User::factory()->superadmin()->create(['email' => 'sa2@example.test']);

        $this->actingAs($a)
            ->post(route('superadmin.users.suspend', $b))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $b->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $a->id,
            'affected_user_id' => $b->id,
            'action' => 'user_suspended',
        ]);
    }

    public function test_superadmin_can_reactivate_suspended_user(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        $target = User::factory()->admin()->create(['status' => 'suspended']);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.reactivate', $target))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'affected_user_id' => $target->id,
            'action' => 'user_reactivated',
        ]);
    }

    public function test_superadmin_can_soft_delete_user_with_activity_log(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.users.destroy', $target))
            ->assertRedirect(route('superadmin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'affected_user_id' => $target->id,
            'action' => 'user_soft_deleted',
        ]);
    }

    public function test_superadmin_cannot_delete_self(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.users.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
    }

    public function test_bulk_destroy_deletes_selected_users_with_confirmation(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        $first = User::factory()->admin()->create();
        $second = User::factory()->teacher()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.bulk-destroy'), [
                'user_ids' => [$first->id, $second->id],
                'confirmation' => 'DELETE',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $first->id]);
        $this->assertSoftDeleted('users', ['id' => $second->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'affected_user_id' => $first->id,
            'action' => 'user_soft_deleted',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'affected_user_id' => $second->id,
            'action' => 'user_soft_deleted',
        ]);
    }

    public function test_bulk_destroy_requires_at_least_one_selected_user(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.bulk-destroy'), [
                'user_ids' => [],
                'confirmation' => 'DELETE',
            ])
            ->assertSessionHasErrors('user_ids');
    }

    public function test_bulk_approve_approves_pending_students(): void
    {
        Event::fake();
        Notification::fake();

        $superAdmin = User::factory()->superadmin()->create();
        $p1 = User::factory()->pending()->create(['email' => 'p1@example.test']);
        $p2 = User::factory()->pending()->create(['email' => 'p2@example.test']);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.bulk-approve'), [
                'user_ids' => [$p1->id, $p2->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $p1->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $p2->id, 'status' => 'active']);
        Notification::assertSentTo([$p1, $p2], RegistrationApprovedNotification::class);
        Event::assertDispatched(RegistrationApproved::class, 2);
    }

    public function test_bulk_approve_requires_at_least_one_selected_user(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.bulk-approve'), [
                'user_ids' => [],
            ])
            ->assertSessionHasErrors('user_ids');
    }

    public function test_bulk_approve_skips_non_pending_users(): void
    {
        Event::fake();
        Notification::fake();

        $superAdmin = User::factory()->superadmin()->create();
        $pending = User::factory()->pending()->create(['email' => 'pending-bulk@example.test']);
        $active = User::factory()->student()->create(['email' => 'active-bulk@example.test', 'status' => 'active']);
        $rejected = User::factory()->student()->create(['email' => 'rejected-bulk@example.test', 'status' => 'rejected']);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.bulk-approve'), [
                'user_ids' => [$pending->id, $active->id, $rejected->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $active->id, 'status' => 'active', 'approved_by' => null]);
        $this->assertDatabaseHas('users', ['id' => $rejected->id, 'status' => 'rejected', 'approved_by' => null]);
        Notification::assertSentTo($pending, RegistrationApprovedNotification::class);
        Notification::assertNotSentTo([$active, $rejected], RegistrationApprovedNotification::class);
        Event::assertDispatched(RegistrationApproved::class, 1);
        $this->assertSame(1, ActivityLog::query()->where('action', 'registration_approved')->count());
    }
}
