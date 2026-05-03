<?php

namespace Tests\Unit;

use App\Services\AuditRunner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditRunnerTest extends TestCase
{
    public function test_it_rejects_localhost_and_private_ips(): void
    {
        $runner = new AuditRunner;

        foreach (['http://localhost', 'http://127.0.0.1', 'http://10.0.0.5', 'http://192.168.1.1'] as $bad) {
            $result = $runner->run($bad);
            $this->assertSame('failed', $result['status'], "{$bad} should be refused");
        }
    }

    public function test_it_normalizes_missing_scheme(): void
    {
        Http::fake([
            '*' => Http::response('<html lang="fr"><head><title>Hi world site</title><meta name="viewport"></head><body><h1>Hi</h1></body></html>', 200, []),
        ]);

        $result = (new AuditRunner)->run('exemple.fr');
        $this->assertSame('completed', $result['status']);
        $this->assertStringStartsWith('https://', $result['results']['url']);
    }

    public function test_strong_security_gives_high_score(): void
    {
        Http::fake([
            '*' => Http::response('<html lang="fr"><head><title>Kodem audit SEO et sécurité</title><meta name="description" content="Une description suffisamment longue pour être bien indexée par Google, couvrant les sujets du site."><meta name="viewport"><link rel="canonical" href="https://x.fr"><meta property="og:title" content="x"><meta name="twitter:card" content="summary"></head><body><h1>H</h1></body></html>', 200, [
                'Strict-Transport-Security' => 'max-age=63072000',
                'Content-Security-Policy' => "default-src 'self'",
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Permissions-Policy' => 'camera=()',
                'Content-Encoding' => 'gzip',
            ]),
        ]);

        $result = (new AuditRunner)->run('https://kodem.fr');

        $this->assertSame('completed', $result['status']);
        $this->assertGreaterThanOrEqual(80, $result['score_security']);
        $this->assertGreaterThanOrEqual(70, $result['score_seo']);
    }

    public function test_weak_security_gives_low_score(): void
    {
        Http::fake([
            '*' => Http::response('<html><body>no head</body></html>', 200, []),
        ]);

        $result = (new AuditRunner)->run('http://bad.example');
        $this->assertSame('completed', $result['status']);
        $this->assertLessThan(40, $result['score_security']);
    }
}
