<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_ship_strong_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'CSP header must be set');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    // -------------------------------------------------------------------------
    // upgrade-insecure-requests : indispensable en HTTPS, ruineux en HTTP local
    // -------------------------------------------------------------------------

    /**
     * Sur un serveur de développement en HTTP, cette directive fait réécrire par
     * le navigateur toute requête http:// en https:// : les POST partent vers une
     * adresse TLS inexistante, la réponse n'arrive jamais et l'interface reste
     * figée — les formulaires « ne font rien ». Elle doit rester absente ici.
     */
    public function test_upgrade_insecure_requests_is_absent_over_plain_http(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('upgrade-insecure-requests', $csp);
    }

    public function test_upgrade_insecure_requests_and_hsts_are_present_over_https(): void
    {
        $response = $this->get('https://localhost/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('upgrade-insecure-requests', $csp);
        $this->assertNotNull(
            $response->headers->get('Strict-Transport-Security'),
            'HSTS doit accompagner une connexion sécurisée'
        );
    }

    /**
     * Derrière un reverse proxy TLS (TrustProxies écoute 127.0.0.1 avec
     * X-Forwarded-Proto), la requête doit être vue comme sécurisée.
     */
    public function test_a_tls_terminating_proxy_is_treated_as_a_secure_context(): void
    {
        $csp = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('upgrade-insecure-requests', $csp);
    }

    // -------------------------------------------------------------------------
    // Polices de la charte : le CSP les bloquait sur toutes les pages
    // -------------------------------------------------------------------------

    public function test_csp_allows_the_brand_font_provider(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        // app.blade.php charge Space Grotesk et JetBrains Mono depuis Bunny :
        // il faut l'autoriser À LA FOIS pour la feuille (style-src) et pour les
        // fichiers de police (font-src), sinon le site retombe sur les polices
        // système sans que rien ne le signale.
        preg_match('/style-src ([^;]+)/', $csp, $style);
        preg_match('/font-src ([^;]+)/', $csp, $font);

        $this->assertStringContainsString('https://fonts.bunny.net', $style[1] ?? '');
        $this->assertStringContainsString('https://fonts.bunny.net', $font[1] ?? '');
    }
}
