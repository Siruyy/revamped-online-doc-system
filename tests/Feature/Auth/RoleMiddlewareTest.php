<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_route_allows_active_verified_student(): void
    {
        $student = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_student_route_blocks_other_roles(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_approved_middleware_rejects_pending_user(): void
    {
        $pendingStudent = User::factory()->pending()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($pendingStudent)
            ->get(route('student.dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
