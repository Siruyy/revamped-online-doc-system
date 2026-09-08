<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\DocumentType;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_publish_public_content_without_exposing_private_or_future_content(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.announcements.store'), ['title' => 'Registrar update', 'body' => 'Public notice', 'audience' => 'all', 'pinned' => false, 'is_published' => true])->assertSessionHasNoErrors();
        $this->post(route('admin.faqs.store'), ['question' => 'How do I pay?', 'answer' => 'Use tracking after clearance.', 'role' => 'all', 'sort_order' => 0])->assertSessionHasNoErrors();
        Announcement::factory()->create(['audience' => 'staff', 'published_at' => now()]);
        Announcement::factory()->create(['audience' => 'all', 'published_at' => now()->addDay()]);
        Announcement::factory()->create(['audience' => 'all', 'published_at' => null]);
        Faq::factory()->create(['role' => 'staff']);
        auth()->logout();
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->has('announcements', 1)->where('announcements.0.title', 'Registrar update')
            ->has('faqs', 1)->where('faqs.0.question', 'How do I pay?'));
    }

    public function test_transfer_name_migration_preserves_fees_and_other_document_types(): void
    {
        $transfer = DocumentType::factory()->create(['code' => 'tor_transfer', 'name' => 'Transcript of Records (valid for Transfer)', 'fee' => 199, 'description' => 'Custom instructions']);
        $other = DocumentType::factory()->create(['code' => 'tor', 'name' => 'Transcript of Records']);
        $migration = require database_path('migrations/2026_09_08_000003_correct_transfer_credentials_name.php');
        $migration->up();
        $migration->up();
        $this->assertSame('Transfer Credentials', $transfer->fresh()->name);
        $this->assertSame('199.00', $transfer->fresh()->fee);
        $this->assertSame('Custom instructions', $transfer->fresh()->description);
        $this->assertSame('Transcript of Records', $other->fresh()->name);
    }

    public function test_mail_diagnostic_identifies_non_delivery_mailer_without_sending_mail(): void
    {
        config(['mail.default' => 'log', 'queue.default' => 'sync']);
        $this->artisan('mail:diagnose')->expectsOutputToContain('does not deliver to inboxes')->assertFailed();
    }

    public function test_mail_diagnostic_checks_resend_key_without_printing_it(): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => null, 'mail.from.address' => 'registrar@school.test', 'queue.default' => 'sync']);
        $this->artisan('mail:diagnose')->expectsOutputToContain('Resend API key: missing')->assertFailed();
        config(['services.resend.key' => 'private-test-key']);
        $this->artisan('mail:diagnose')->expectsOutputToContain('Resend API key: configured')->doesntExpectOutputToContain('private-test-key')->assertSuccessful();
    }
}
