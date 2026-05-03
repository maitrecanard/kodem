<?php

namespace Tests\Unit;

use App\Services\AuditRecommendations;
use App\Services\AuditRunner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditRecommendationsTest extends TestCase
{
    public function test_every_check_key_has_a_recommendation(): void
    {
        $expectedKeys = [
            // SEO
            'http_200', 'title', 'meta_description', 'h1', 'viewport',
            'html_lang', 'canonical', 'og', 'twitter', 'compressed',
            // Sécurité
            'https', 'hsts', 'csp', 'x_frame', 'x_content_type',
            'referrer_policy', 'permissions_policy', 'server_header',
            'x_powered_by', 'cookies_secure',
        ];

        foreach ($expectedKeys as $key) {
            $rec = AuditRecommendations::for($key);
            $this->assertNotNull($rec, "Missing recommendation for '{$key}'");
            $this->assertArrayHasKey('fix', $rec);
            $this->assertArrayHasKey('effort', $rec);
            $this->assertNotEmpty($rec['fix']);
        }
    }

    public function test_unknown_key_returns_null(): void
    {
        $this->assertNull(AuditRecommendations::for('does_not_exist'));
    }

    public function test_failed_checks_carry_recommendations_in_audit_result(): void
    {
        Http::fake(['*' => Http::response('<html><body>no head</body></html>', 200, [])]);

        $result = (new AuditRunner)->run('http://bad.example');

        foreach ($result['results']['security']['checks'] as $c) {
            if ($c['status'] === 'pass') {
                $this->assertArrayNotHasKey('recommendation', $c, "'{$c['key']}' passed — no recommendation expected");
            } else {
                $this->assertArrayHasKey('recommendation', $c, "'{$c['key']}' failed — recommendation expected");
            }
        }

        $plan = $result['results']['action_plan'];
        $this->assertNotEmpty($plan['items']);
        $this->assertGreaterThan(0, $plan['potential_gain_total']);
    }

    public function test_action_plan_is_sorted_fail_first_then_by_gain(): void
    {
        Http::fake(['*' => Http::response('<html><body></body></html>', 200, [])]);

        $plan = (new AuditRunner)->run('http://sort.example')['results']['action_plan'];

        $items = $plan['items'];
        $seenWarn = false;
        foreach ($items as $it) {
            if ($it['status'] === 'warn') {
                $seenWarn = true;
            } elseif ($it['status'] === 'fail') {
                $this->assertFalse($seenWarn, "fail item appeared after a warn item");
            }
        }

        // À l'intérieur de la tranche 'fail', les gains doivent décroître.
        $fails = array_values(array_filter($items, fn ($i) => $i['status'] === 'fail'));
        for ($i = 1; $i < count($fails); $i++) {
            $this->assertGreaterThanOrEqual(
                $fails[$i]['potential_gain'],
                $fails[$i - 1]['potential_gain'],
                'fail items must be sorted by potential_gain desc'
            );
        }
    }

    public function test_perfect_site_has_empty_action_plan_and_zero_gain(): void
    {
        $strongHeaders = [
            'Strict-Transport-Security' => 'max-age=63072000',
            'Content-Security-Policy' => "default-src 'self'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=()',
            'Content-Encoding' => 'gzip',
        ];
        $html = '<html lang="fr"><head>'.
            '<meta name="viewport" content="width=device-width,initial-scale=1">'.
            '<title>Kodem audit SEO et audit de sécurité</title>'.
            '<meta name="description" content="Une description suffisamment longue pour passer le test de longueur, bien au-delà de 70 caractères pour éviter le warn.">'.
            '<link rel="canonical" href="https://ok.example/">'.
            '<meta property="og:title" content="x">'.
            '<meta name="twitter:card" content="summary">'.
            '</head><body><h1>OK</h1></body></html>';

        Http::fake(['*' => Http::response($html, 200, $strongHeaders)]);

        $result = (new AuditRunner)->run('https://ok.example');
        $plan = $result['results']['action_plan'];

        // Score global élevé attendu et plus aucun fail.
        $this->assertGreaterThanOrEqual(90, $result['score_total']);
        foreach ($plan['items'] as $it) {
            $this->assertNotSame('fail', $it['status'], "Unexpected fail for '{$it['key']}'");
        }
        // Le gain potentiel doit refléter ce qui reste (≤ 10 points).
        $this->assertLessThanOrEqual(10, $plan['potential_gain_total']);
    }
}
