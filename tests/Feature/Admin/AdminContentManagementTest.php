<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_document_types(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'name' => 'Enrollment Certificate',
            'description' => 'Freshly generated enrollment cert.',
            'category' => 'Academic',
            'fee' => 120,
            'processing_days' => 2,
            'is_active' => true,
        ])->assertRedirect();

        $type = DocumentType::query()->where('name', 'Enrollment Certificate')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.document-types.update', $type), [
            'name' => 'Enrollment Certificate',
            'description' => 'Updated description',
            'category' => 'Academic',
            'fee' => 150,
            'processing_days' => 3,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('document_types', ['id' => $type->id, 'fee' => '150.00']);

        $this->actingAs($admin)->delete(route('admin.document-types.destroy', $type))->assertRedirect();
    }

    public function test_admin_can_crud_announcements_and_faqs(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'System Maintenance',
            'body' => 'Maintenance tonight at 9pm.',
            'audience' => 'all',
            'pinned' => true,
            'is_published' => true,
        ])->assertRedirect();

        $announcement = Announcement::query()->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.announcements.update', $announcement), [
            'title' => 'System Maintenance Updated',
            'body' => 'Maintenance moved to 10pm.',
            'audience' => 'staff',
            'pinned' => false,
            'is_published' => true,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'role' => 'student',
            'question' => 'How long does processing take?',
            'answer' => 'Usually 3-5 working days.',
            'sort_order' => 1,
        ])->assertRedirect();

        $faq = Faq::query()->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.faqs.update', $faq), [
            'role' => 'all',
            'question' => 'How long does processing take?',
            'answer' => 'Updated answer',
            'sort_order' => 2,
        ])->assertRedirect();
    }

    public function test_admin_can_view_clearance_monitor_and_reports(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create();

        $this->actingAs($admin)->get(route('admin.clearances.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.clearances.show', $clearance))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    }

    public function test_admin_notifications_page_and_mark_read_work(): void
    {
        $admin = $this->createAdmin();
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'tests.notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'data' => ['type' => 'system', 'message' => 'Admin notification'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $notificationId = $admin->notifications()->firstOrFail()->id;

        $this->actingAs($admin)->get(route('admin.notifications.index'))->assertOk();
        $this->actingAs($admin)->post(route('admin.notifications.mark-read', $notificationId))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.notifications.mark-all-read'))->assertRedirect();
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
