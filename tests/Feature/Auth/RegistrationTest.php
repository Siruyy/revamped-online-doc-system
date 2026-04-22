<?php

namespace Tests\Feature\Auth;

use App\Events\RegistrationSubmitted as RegistrationSubmittedBroadcast;
use App\Models\User;
use App\Notifications\RegistrationSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_register_as_pending_students_and_notify_superadmins(): void
    {
        Notification::fake();
        Event::fake([RegistrationSubmittedBroadcast::class]);

        $superAdmin = User::factory()->superadmin()->create();

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
        $response->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'student',
            'status' => 'pending',
        ]);

        Notification::assertSentTo($superAdmin, RegistrationSubmittedNotification::class);
        Event::assertDispatched(RegistrationSubmittedBroadcast::class);
    }
}
