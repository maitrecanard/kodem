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

    // -------------------------------------------------------------------------
    // Garde-fou SSRF
    // -------------------------------------------------------------------------

    /**
     * 169.254.169.254 est l'endpoint de métadonnées des instances cloud
     * (AWS/GCP/Azure) : il expose des identifiants. L'ancien filtre, qui ne
     * regardait que 10/172.16-31/192.168, le laissait passer.
     */
    public function test_it_rejects_cloud_metadata_and_reserved_ranges(): void
    {
        $runner = new AuditRunner;

        $bad = [
            'http://169.254.169.254/latest/meta-data/',
            'http://169.254.170.2/',
            'http://0.0.0.0',
            'http://[::1]/',
            'http://172.31.255.1',
        ];

        foreach ($bad as $url) {
            $this->assertSame('failed', $runner->run($url)['status'], "{$url} doit être refusée");
        }
    }

    public function test_it_rejects_internal_tlds_and_non_http_schemes(): void
    {
        $runner = new AuditRunner;

        foreach (['http://serveur.internal', 'http://nas.local', 'file:///etc/passwd', 'gopher://evil.test'] as $url) {
            $this->assertSame('failed', $runner->run($url)['status'], "{$url} doit être refusée");
        }
    }

    public function test_a_redirect_towards_an_internal_address_is_refused(): void
    {
        // Une URL publique qui redirige vers une adresse interne : le client HTTP
        // suivrait la redirection sans re-contrôler. Chaque saut doit être revalidé.
        Http::fake([
            'https://public.example' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        ]);

        $result = (new AuditRunner)->run('https://public.example');

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('non autorisée', (string) $result['error']);
    }

    public function test_it_follows_a_legitimate_redirect_and_reports_the_final_url(): void
    {
        Http::fake([
            'https://exemple-depart.test' => Http::response('', 301, ['Location' => 'https://exemple-arrivee.test/accueil']),
            'https://exemple-arrivee.test/accueil' => Http::response(
                '<html lang="fr"><head><title>Page d\'arrivée bien titrée</title></head><body><h1>Ok</h1></body></html>',
                200
            ),
        ]);

        $result = (new AuditRunner)->run('https://exemple-depart.test');

        $this->assertSame('completed', $result['status']);
        $this->assertSame('https://exemple-arrivee.test/accueil', $result['results']['url']);
        $this->assertSame('https://exemple-depart.test', $result['results']['requested_url']);
    }

    // -------------------------------------------------------------------------
    // Analyse DOM (l'ancien parsing regex produisait des faux négatifs)
    // -------------------------------------------------------------------------

    /**
     * `<meta content="…" name="description">` est valide et courant. L'ancienne
     * regex exigeait `name` avant `content` et déclarait la description absente.
     */
    public function test_meta_description_is_found_whatever_the_attribute_order(): void
    {
        $description = 'Une description suffisamment longue pour être considérée comme correctement optimisée ici.';

        Http::fake([
            '*' => Http::response(
                '<html lang="fr"><head><title>Un titre parfaitement correct</title>'
                ."<meta content=\"{$description}\" name=\"Description\">"
                .'</head><body><h1>Titre</h1></body></html>',
                200
            ),
        ]);

        $result = (new AuditRunner)->run('https://exemple.test');

        $this->assertSame($description, $result['results']['seo']['summary']['description']);

        $check = collect($result['results']['seo']['checks'])->firstWhere('key', 'meta_description');
        $this->assertSame('pass', $check['status']);
    }

    public function test_canonical_is_found_in_a_multi_valued_rel_and_with_single_quotes(): void
    {
        Http::fake([
            '*' => Http::response(
                "<html lang='fr'><head><title>Titre suffisamment long</title>"
                ."<link rel='alternate canonical' href='https://exemple.test/page'>"
                .'</head><body><h1>H</h1></body></html>',
                200
            ),
        ]);

        $result = (new AuditRunner)->run('https://exemple.test');

        $this->assertSame('https://exemple.test/page', $result['results']['seo']['summary']['canonical']);
    }

    public function test_title_and_h1_are_returned_as_plain_text(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html lang="fr"><head><title>Titre avec assez de caractères</title></head>'
                .'<body><h1>Bonjour <span>le</span> monde</h1></body></html>',
                200
            ),
        ]);

        $summary = (new AuditRunner)->run('https://exemple.test')['results']['seo']['summary'];

        $this->assertSame('Titre avec assez de caractères', $summary['title']);
        $this->assertSame('Bonjour le monde', $summary['h1']);
    }

    // -------------------------------------------------------------------------
    // Régressions diverses
    // -------------------------------------------------------------------------

    /**
     * L'ancien calcul était `$stats['total_time'] ?? 0 * 1000` : `*` étant
     * prioritaire sur `??`, la multiplication portait sur la valeur de repli et
     * le temps (en secondes) était arrondi à 0.
     */
    public function test_response_time_is_reported_in_milliseconds(): void
    {
        Http::fake(['*' => Http::response('<html><head><title>Titre assez long ici</title></head><body></body></html>', 200)]);

        $result = (new AuditRunner)->run('https://exemple.test');

        $this->assertArrayHasKey('response_time_ms', $result['results']);
        $this->assertIsInt($result['results']['response_time_ms']);
        $this->assertGreaterThanOrEqual(0, $result['results']['response_time_ms']);
    }

    public function test_robots_and_sitemap_are_scored_not_only_collected(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('User-agent: *', 200),
            '*/sitemap.xml' => Http::response('', 404),
            '*' => Http::response('<html lang="fr"><head><title>Titre assez long ici</title></head><body><h1>H</h1></body></html>', 200),
        ]);

        $checks = collect((new AuditRunner)->run('https://exemple.test')['results']['seo']['checks']);

        $this->assertSame('pass', $checks->firstWhere('key', 'robots_txt')['status']);
        $this->assertSame('warn', $checks->firstWhere('key', 'sitemap_xml')['status']);
    }
}
