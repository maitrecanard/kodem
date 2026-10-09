<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            try {
                PageVisit::create([
                    'url' => substr($request->path(), 0, 255),
                    'referer' => substr((string) $request->headers->get('referer'), 0, 255) ?: null,
                    'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
                    'ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
                ]);
            } catch (\Throwable $e) {
                // Ne jamais bloquer la requête sur l'analytics.
                report($e);
            }
        }

        return $response;
    }

    protected function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $path = $request->path();
        $skip = ['admin', 'admin/*', 'login', 'register', 'password/*', 'dashboard', 'profile', 'logout', 'up', 'build/*', 'storage/*'];

        foreach ($skip as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        $ua = strtolower((string) $request->userAgent());
        // « kodem-sonde » : sonde de surveillance interne, une requête par minute. Sans cette
        // exclusion elle représentait 83 % des pages vues enregistrées en septembre 2026.
        if ($ua && preg_match('/bot|crawler|spider|headless|kodem-sonde/i', $ua)) {
            return false;
        }

        return true;
    }
}
