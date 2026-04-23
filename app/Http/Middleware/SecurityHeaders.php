<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Ziggy @routes() emits an inline script — needs 'unsafe-inline' on script-src.
        // Google Fonts in app.blade.php need explicit style-src / font-src / connect-src.
        // Vite dev server runs on another origin (e.g. :5173) — allow when public/hot exists.
        $scriptSrc = "'self' 'unsafe-inline'";

        if (Vite::isRunningHot()) {
            $scriptSrc .= ' http://127.0.0.1:5173 http://localhost:5173';
        }

        $connectSrc = "'self' ws://127.0.0.1:* ws://localhost:* wss://127.0.0.1:* wss://localhost:* ".
            'http://127.0.0.1:* http://localhost:* https://fonts.googleapis.com https://fonts.gstatic.com';

        if (Vite::isRunningHot()) {
            $connectSrc .= ' ws://127.0.0.1:5173 ws://localhost:5173';
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data:; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
            "font-src 'self' data: https://fonts.gstatic.com; ".
            "script-src {$scriptSrc}; frame-ancestors 'none'; ".
            "connect-src {$connectSrc};"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
