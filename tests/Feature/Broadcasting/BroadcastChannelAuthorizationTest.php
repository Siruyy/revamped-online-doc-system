<?php

namespace Tests\Feature\Broadcasting;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_authorize_own_user_private_channel(): void
    {
        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$student->id,
            'socket_id' => '123.456',
        ])->assertOk();
    }

    public function test_student_cannot_authorize_another_users_private_channel(): void
    {
        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $other = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$other->id,
            'socket_id' => '123.456',
        ])->assertForbidden();
    }

    public function test_admin_can_authorize_role_admin_channel(): void
    {
        $admin = User::factory()->admin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.admin',
            'socket_id' => '123.456',
        ])->assertOk();
    }

    public function test_superadmin_can_authorize_role_admin_channel(): void
    {
        $super = User::factory()->superadmin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($super)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.admin',
            'socket_id' => '123.456',
        ])->assertOk();
    }

    public function test_student_cannot_authorize_role_admin_channel(): void
    {
        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.admin',
            'socket_id' => '123.456',
        ])->assertForbidden();
    }

    public function test_superadmin_can_authorize_role_superadmin_channel(): void
    {
        $super = User::factory()->superadmin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($super)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.superadmin',
            'socket_id' => '123.456',
        ])->assertOk();
    }

    public function test_admin_cannot_authorize_role_superadmin_channel(): void
    {
        $admin = User::factory()->admin()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.superadmin',
            'socket_id' => '123.456',
        ])->assertForbidden();
    }

    public function test_signatory_can_authorize_matching_department_channel(): void
    {
        $teacher = User::factory()->create([
            'role' => 'librarian',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($teacher)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.department.librarian',
            'socket_id' => '123.456',
        ])->assertOk();
    }

    public function test_signatory_cannot_authorize_other_department_channel(): void
    {
        $teacher = User::factory()->create([
            'role' => 'librarian',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($teacher)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-role.department.dean',
            'socket_id' => '123.456',
        ])->assertForbidden();
    }

    public function test_chat_channel_authorizes_participants_only(): void
    {
        $alice = User::factory()->student()->create(['status' => 'active', 'email_verified_at' => now()]);
        $bob = User::factory()->student()->create(['status' => 'active', 'email_verified_at' => now()]);

        $message = Message::factory()->create([
            'sender_id' => $alice->id,
            'receiver_id' => $bob->id,
            'body' => 'Hello',
        ]);

        $this->actingAs($alice)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-chat.'.$message->id,
            'socket_id' => '123.456',
        ])->assertOk();

        $stranger = User::factory()->student()->create(['status' => 'active', 'email_verified_at' => now()]);

        $this->actingAs($stranger)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-chat.'.$message->id,
            'socket_id' => '123.456',
        ])->assertForbidden();
    }
}
