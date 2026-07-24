<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_active_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_active_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->student()->create([
            'username' => 'student.login',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'login' => 'STUDENT.LOGIN',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_pending_users_can_not_authenticate(): void
    {
        $user = User::factory()->pending()->create();

        $response = $this->from('/login')->post('/login', [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_suspended_users_can_not_authenticate(): void
    {
        $user = User::factory()->suspended()->create();

        $response = $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_account_is_locked_after_ten_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->from('/login')->post('/login', [
                'login' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'login' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString(
            'Account locked due to repeated failed logins',
            session('errors')->first('login')
        );
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
