<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('audit');
    }

    public function test_submitting_a_valid_url_creates_and_runs_audit(): void
    {
        Http::fake([
            '*' => Http::response($this->goodHtml(), 200, $this->strongHeaders()),
        ]);

        $response = $this->post('/audit', ['url' => 'https://example.com']);

        $response->assertRedirect();
        $this->assertDatabaseCount('audits', 1);
        $audit = Audit::first();
        $this->assertSame('completed', $audit->status);
        $this->assertNotNull($audit->score_total);
        $this->assertGreaterThan(50, $audit->score_total);
        $this->assertNull($audit->paid_at, 'audit should start unpaid');
        $this->assertSame(2900, $audit->price_cents);
    }

    public function test_bad_url_is_rejected_by_validation(): void
    {
        $this->post('/audit', ['url' => ''])->assertSessionHasErrors('url');
        $this->assertDatabaseCount('audits', 0);
    }

    public function test_unpaid_result_page_shows_teaser_only(): void
    {
        Http::fake(['*' => Http::response($this->goodHtml(), 200, $this->strongHeaders())]);

        $this->post('/audit', ['url' => 'https://example.org']);
        $audit = Audit::first();

        $this->get('/audit/'.$audit->uuid)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditResult')
                ->where('paid', false)
                ->where('audit.uuid', $audit->uuid)
                ->where('audit.score_total', fn ($v) => is_int($v) && $v > 0)
                ->where('audit.score_seo', null)
                ->where('audit.score_security', null)
                ->where('audit.results', null)
                ->has('audit.teaser')
                ->has('price.label')
            );
    }

    public function test_paid_result_page_shows_full_report(): void
    {
        Http::fake(['*' => Http::response($this->goodHtml(), 200, $this->strongHeaders())]);

        $this->post('/audit', ['url' => 'https://example.net']);
        $audit = Audit::first();
        $audit->update(['paid_at' => now(), 'payment_reference' => 'TEST-123']);

        $this->get('/audit/'.$audit->uuid)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditResult')
                ->where('paid', true)
                ->where('audit.score_seo', fn ($v) => is_int($v))
                ->where('audit.score_security', fn ($v) => is_int($v))
                ->has('audit.results.seo.checks')
                ->has('audit.results.security.checks')
            );
    }

    public function test_admin_sees_full_report_without_paying(): void
    {
        Http::fake(['*' => Http::response($this->goodHtml(), 200, $this->strongHeaders())]);

        $this->post('/audit', ['url' => 'https://admin.example']);
        $audit = Audit::first();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get('/audit/'.$audit->uuid)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('paid', true)
                ->has('audit.results.seo.checks')
            );
    }

    public function test_audit_scores_lower_when_security_headers_missing(): void
    {
        Http::fake(['*' => Http::response($this->minimalHtml(), 200, [])]);

        $this->post('/audit', ['url' => 'http://weak.example']);
        $audit = Audit::first();

        $this->assertSame('completed', $audit->status);
        $this->assertLessThan(50, $audit->score_security);
    }

    protected function goodHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kodem — audit SEO et audit de sécurité automatisés</title>
<meta name="description" content="Kodem, société de développement web et d'hébergement proposant des audits SEO et de sécurité automatisés en ligne.">
<meta property="og:title" content="Kodem">
<meta property="og:description" content="Audit">
<meta name="twitter:card" content="summary">
<link rel="canonical" href="https://example.com/">
</head>
<body>
<h1>Audit</h1>
<p>hello</p>
</body>
</html>
HTML;
    }

    protected function minimalHtml(): string
    {
        return '<html><body>no head no heart</body></html>';
    }

    /**
     * @return array<string,string>
     */
    protected function strongHeaders(): array
    {
        return [
            'Content-Type' => 'text/html; charset=utf-8',
            'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
            'Content-Security-Policy' => "default-src 'self'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=()',
            'Content-Encoding' => 'gzip',
        ];
    }
}
