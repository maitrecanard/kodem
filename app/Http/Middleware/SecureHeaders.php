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

        $isSecureContext = $request->secure() || app()->environment('production');

        if ($isSecureContext) {
            $headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload';
        }

        $scriptSrc = "'self'";
        // Les polices de la charte (Space Grotesk, JetBrains Mono) sont servies
        // par fonts.bunny.net depuis app.blade.php : sans ces deux origines, le
        // CSP bloque la feuille ET les fichiers de police, et le site retombe
        // sur les polices système.
        $styleSrc = "'self' 'unsafe-inline' https://fonts.bunny.net";
        $fontSrc = "'self' data: https://fonts.bunny.net";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            // Vite dev server HMR
            $scriptSrc .= " 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173";
            $styleSrc .= ' http://localhost:5173 http://127.0.0.1:5173';
            $connectSrc .= ' ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173';
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob:",
            "font-src {$fontSrc}",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "connect-src {$connectSrc}",
            "object-src 'none'",
        ];

        // `upgrade-insecure-requests` réécrit toute requête http:// de la page en
        // https://. En production c'est souhaitable ; en développement sur un
        // serveur HTTP local, cela envoie chaque requête non-GET vers une adresse
        // TLS inexistante — la réponse n'arrive jamais et l'interface reste figée
        // (formulaires qui « ne font rien »). On l'émet donc aux mêmes conditions
        // que HSTS : connexion déjà sécurisée, ou production.
        if ($isSecureContext) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $directives);

        $headers['Content-Security-Policy'] = $csp;

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
