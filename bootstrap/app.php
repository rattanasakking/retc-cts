<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Trust a reverse proxy/load balancer (Nginx, Cloudflare, etc.) in
        // front of the app so Request::isSecure() and url()/route() honor
        // X-Forwarded-Proto correctly instead of seeing the proxy's plain
        // HTTP hop and generating http:// links or non-secure session
        // cookies behind an HTTPS edge. Comma-separated IPs/CIDRs — leave
        // TRUSTED_PROXIES unset to trust none (safe default for a server
        // reachable directly, e.g. local/dev).
        $middleware->trustProxies(
            at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))) ?: null,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
