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
        // Trust the local reverse proxy (nginx/Apache/Cloudflare tunnel) so the
        // app reads the real client IP and scheme from the X-Forwarded-* headers.
        // Required for TrackVisit (correct visitor IP) and SecureHeaders (HTTPS detection).
        //
        // Production only: in local/dev (Herd, Valet, Sail, artisan serve) trusting a
        // proxy can force HTTPS or skew the scheme/host and break the dev workflow.
        // Gated on isProduction() (resolved env) rather than env('APP_ENV'), which
        // returns null once `php artisan config:cache` is run in production.
        if (app()->isProduction()) {
            $middleware->trustProxies(at: [
                '127.0.0.1',
            ], headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PORT);
        }

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
