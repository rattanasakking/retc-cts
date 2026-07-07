<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every response. Deliberately does NOT set a
 * Content-Security-Policy — Alpine.js (bundled with Livewire, used
 * throughout this app's sidebar/dropdowns/modals) evaluates x-data
 * expressions via `new Function()`, which needs 'unsafe-eval' in the CSP.
 * Ship a strict CSP only after testing it against every Alpine directive in
 * the app, or switch to Alpine's CSP-safe build first.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        return $response;
    }
}
