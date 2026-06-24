<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Validates the PRODUCTION branch of the trusted-proxy config in bootstrap/app.php.
 *
 * The application is booted with APP_ENV=production so that app()->isProduction()
 * is true and trustProxies() is registered. The test client connects from
 * 127.0.0.1 (the trusted proxy), so X-Forwarded-* headers must be honored.
 *
 * @see TrustProxiesTest for the non-production (disabled) case.
 */
class TrustProxiesProductionTest extends TestCase
{
    /**
     * Boot the app as production by forcing APP_ENV before bootstrap/app.php runs.
     * Laravel loads its .env with an immutable Dotenv repository, so a value set
     * here is not overwritten by the .env file.
     */
    public function createApplication()
    {
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';

        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore the testing environment so other test classes are unaffected.
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
    }

    public function test_environment_is_production(): void
    {
        // Guards the assumption the proxy assertions rely on.
        $this->assertTrue($this->app->isProduction());
    }

    public function test_forwarded_headers_from_trusted_proxy_are_honored_in_production(): void
    {
        Route::get('/_test/proxy-probe', function (Request $request) {
            return response()->json([
                'ip' => $request->ip(),
                'secure' => $request->isSecure(),
                'host' => $request->getHost(),
            ]);
        });

        $response = $this->get('/_test/proxy-probe', [
            'X-Forwarded-For' => '203.0.113.7',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tehcare.example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'ip' => '203.0.113.7',
            'secure' => true,
            'host' => 'tehcare.example.com',
        ]);
    }
}
