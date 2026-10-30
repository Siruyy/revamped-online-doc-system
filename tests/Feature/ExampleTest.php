<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\PaymentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_the_public_welcome_experience(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->where('canLogin', true)
                ->where('canRegister', true)
                ->has('laravelVersion')
                ->has('phpVersion'));

        $this->get(route('public.requests.create'))->assertOk();
        $this->get(route('track-document'))->assertOk();
        $this->get(route('public.pricelist'))->assertOk();
    }

    public function test_landing_page_lists_common_active_documents_in_display_order(): void
    {
        DocumentType::query()->create(['code' => 'cert_grades', 'name' => 'Certificate of Grades', 'category' => 'Certification', 'fee' => 120, 'fee_formula' => 'flat', 'processing_days' => 3, 'is_active' => true]);
        DocumentType::query()->create(['code' => 'tor', 'name' => 'Transcript of Records', 'category' => 'Academic', 'fee' => 165, 'fee_formula' => 'per_page', 'processing_days' => 14, 'is_active' => true]);
        DocumentType::query()->create(['code' => 'cav', 'name' => 'CAV', 'category' => 'Special', 'fee' => 140, 'fee_formula' => 'flat', 'processing_days' => 3, 'is_active' => false]);
        DocumentType::query()->create(['code' => 'grades_printout', 'name' => 'Grades Print-Out', 'category' => 'Special', 'fee' => 20, 'fee_formula' => 'per_page', 'processing_days' => 3, 'is_active' => true]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('commonDocuments', 2)
                ->where('commonDocuments.0.code', 'tor')
                ->where('commonDocuments.0.processing_days', 14)
                ->where('commonDocuments.1.code', 'cert_grades')
                ->missing('commonDocuments.0.offices'));
    }

    public function test_landing_page_exposes_active_payment_details(): void
    {
        PaymentProfile::query()->create([
            'bank_name' => 'Test Bank',
            'account_name' => 'SVCI',
            'account_number' => '1234567890',
            'instructions' => 'Use the request reference number.',
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('paymentProfile.bank_name', 'Test Bank')
                ->where('paymentProfile.account_number', '1234567890')
                ->where('paymentProfile.instructions', 'Use the request reference number.')
                ->where('paymentProfile.qr_url', null)
            );
    }

    public function test_not_found_errors_use_the_branded_error_page(): void
    {
        $this->get('/missing-ui-polish-page')
            ->assertNotFound()
            ->assertSee('SVCI Docs')
            ->assertSee('Page not found')
            ->assertSee('Return home');
    }

    public function test_common_error_views_render_branded_recovery_copy(): void
    {
        $this->view('errors.403')->assertSee('Access not allowed')->assertSee('Return home');
        $this->view('errors.419')->assertSee('Session expired')->assertSee('Go to login');
        $this->view('errors.500')->assertSee('Something went wrong')->assertSee('SVCI Docs');
    }
}
