<?php

namespace Tests\Feature;

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

        $this->assertFileExists(public_path('images/landing/registrar-service.png'));
        $this->assertFileExists(public_path('images/landing/study-materials.jpg'));
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
