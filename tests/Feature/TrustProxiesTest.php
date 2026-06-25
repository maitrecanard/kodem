<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Validates the trusted-proxy configuration declared in bootstrap/app.php
 * in the NON-production case (here: the "testing" environment).
 *
 * trustProxies() is gated on app()->isProduction(), so outside production the
 * X-Forwarded-* headers must be ignored — even though the test client connects
 * from 127.0.0.1, that address is NOT registered as a trusted proxy in dev/test.
 *
 * @see TrustProxiesProductionTest for the production (enabled) case.
 */
class TrustProxiesTest extends TestCase
{
    private function registerProbeRoute(): void
    {
        Route::get('/_test/proxy-probe', function (Request $request) {
            return response()->json([
                'ip' => $request->ip(),
                'secure' => $request->isSecure(),
                'host' => $request->getHost(),
            ]);
        });
    }

    public function test_environment_is_not_production(): void
    {
        // Guards the assumption the other assertions rely on.
        $this->assertFalse($this->app->isProduction());
    }

    public function test_forwarded_headers_are_ignored_outside_production(): void
    {
        $this->registerProbeRoute();

        $response = $this->get('/_test/proxy-probe', [
            'X-Forwarded-For' => '203.0.113.7',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tehcare.example.com',
        ]);

        $response->assertOk();
        // Proxy not trusted in dev/test => forwarded headers must NOT be applied.
        $response->assertJson([
            'secure' => false,
            'host' => 'localhost',
        ]);
        $this->assertNotSame('203.0.113.7', $response->json('ip'));
    }
}
