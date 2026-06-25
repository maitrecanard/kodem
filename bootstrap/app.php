<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the local reverse proxy (Caddy/nginx) so the app reads the real
        // client IP and scheme from the X-Forwarded-* headers.
        // Required for TrackVisit (correct visitor IP) and SecureHeaders (HTTPS detection).
        //
        // NOTE: this closure runs while the application is being BUILT, before the
        // environment is bootstrapped. app()->isProduction() and env() are NOT
        // available here — calling them throws "Class \"env\" does not exist".
        // So we trust 127.0.0.1 unconditionally: in local dev without a reverse
        // proxy, no X-Forwarded-* headers arrive from 127.0.0.1, making this a no-op.
        $middleware->trustProxies(at: [
            '127.0.0.1',
        ], headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PORT);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecureHeaders::class,
            \App\Http\Middleware\TrackVisit::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            '2fa' => \App\Http\Middleware\Require2FA::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
