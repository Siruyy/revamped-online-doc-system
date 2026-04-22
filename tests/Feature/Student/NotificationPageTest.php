<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_notifications_page(): void
    {
        $student = $this->createActiveStudent();
        $this->seedNotification($student);

        $this->actingAs($student)->get(route('student.notifications.index'))->assertOk();
    }

    public function test_student_can_mark_notification_as_read(): void
    {
        $student = $this->createActiveStudent();
        $notificationId = $this->seedNotification($student)->id;

        $this->actingAs($student)
            ->post(route('student.notifications.mark-read', $notificationId))
            ->assertRedirect();

        $this->assertNotNull($student->fresh()->notifications()->firstOrFail()->read_at);
    }

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        $student = $this->createActiveStudent();
        $this->seedNotification($student);
        $this->seedNotification($student);

        $this->actingAs($student)
            ->post(route('student.notifications.mark-all-read'))
            ->assertRedirect();

        $this->assertCount(0, $student->fresh()->unreadNotifications);
    }

    private function createActiveStudent(): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function seedNotification(User $student): DatabaseNotification
    {
        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'tests.notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'data' => ['type' => 'system', 'message' => 'Test notification'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
