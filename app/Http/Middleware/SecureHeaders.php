<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            'X-XSS-Protection' => '1; mode=block',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        if ($request->secure() || app()->environment('production')) {
            $headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload';
        }

        $scriptSrc = "'self'";
        $styleSrc = "'self' 'unsafe-inline'";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            // Vite dev server HMR
            $scriptSrc .= " 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173";
            $styleSrc .= ' http://localhost:5173 http://127.0.0.1:5173';
            $connectSrc .= ' ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "connect-src {$connectSrc}",
            "object-src 'none'",
            "upgrade-insecure-requests",
        ]);

        $headers['Content-Security-Policy'] = $csp;

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
