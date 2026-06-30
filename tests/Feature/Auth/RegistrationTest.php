<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_to_public_request_form(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('public.requests.create'));
    }

    public function test_registration_post_redirects_to_public_request_form_without_creating_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'course' => 'BSIT',
            'year_level' => 2,
            'student_id' => 'SVCI-000001',
            'contact_number' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('public.requests.create'));
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
        $this->assertSame(0, User::query()->where('role', 'student')->count());
    }
}
