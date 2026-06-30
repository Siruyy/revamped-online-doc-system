<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_content_security_policy_allows_current_vite_hot_origin(): void
    {
        $this->withHotFile('http://127.0.0.1:5190', function (): void {
            $response = $this->get('/');

            $csp = $response->headers->get('Content-Security-Policy');

            $this->assertStringContainsString('http://127.0.0.1:5190', $csp);
            $this->assertStringContainsString('ws://127.0.0.1:5190', $csp);
        });
    }

    public function test_content_security_policy_rejects_non_local_vite_hot_origin(): void
    {
        $this->withHotFile('http://attacker.example:5190', function (): void {
            $response = $this->get('/');

            $csp = $response->headers->get('Content-Security-Policy');

            $this->assertStringNotContainsString('attacker.example', $csp);
            $this->assertStringContainsString('http://127.0.0.1:5173', $csp);
            $this->assertStringContainsString('ws://127.0.0.1:5173', $csp);
        });
    }

    public function test_content_security_policy_uses_configured_reverb_host(): void
    {
        Config::set('broadcasting.connections.reverb.options.host', 'reverb.example.test');

        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('wss://reverb.example.test', $csp);
        $this->assertStringContainsString('ws://reverb.example.test', $csp);
    }

    /**
     * @param  callable(): void  $callback
     */
    private function withHotFile(string $contents, callable $callback): void
    {
        $hotPath = public_path('hot');
        $previousHot = file_exists($hotPath) ? file_get_contents($hotPath) : null;

        file_put_contents($hotPath, $contents);

        try {
            $callback();
        } finally {
            if ($previousHot === null) {
                @unlink($hotPath);
            } else {
                file_put_contents($hotPath, $previousHot);
            }
        }
    }
}
