<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\ReadsJsonLd;
use Tests\TestCase;

class SeoRefonteTest extends TestCase
{
    use ReadsJsonLd;
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // 1. Page hébergement — composant, meta et prestation passés correctement
    // -------------------------------------------------------------------------

    public function test_hebergement_page_renders_correct_inertia_component_with_seo_meta_and_prestation(): void
    {
        $this->get('/hebergement-web')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Hebergement')
                ->where('meta.title', fn (string $title) => str_contains($title, 'Hébergement') && str_contains($title, 'managé')
                )
                ->where('prestation.slug', 'hebergement-web')
                ->where('meta.robots', 'noindex, follow')
            );
    }

    // -------------------------------------------------------------------------
    // 2. sitemap.xml — validité XML, URLs attendues, absence de routes privées
    // -------------------------------------------------------------------------

    public function test_sitemap_xml_is_valid_and_exposes_only_public_routes(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();

        $this->assertStringContainsString(
            'xml',
            $response->headers->get('Content-Type', ''),
            'Content-Type doit mentionner xml'
        );

        $content = $response->getContent();

        $this->assertStringContainsString(
            '<urlset',
            $content,
            'Le sitemap doit contenir l\'élément racine <urlset>'
        );

        // Vote du 2026-09-29 : catalogue, hébergement, audits et monitoring hors sitemap.
        foreach (['/prestations', '/hebergement-web', '/audit', '/monitoring'] as $retired) {
            $this->assertStringNotContainsString(
                '<loc>'.url($retired).'</loc>',
                $content,
                "Le sitemap ne doit plus contenir {$retired}"
            );
        }

        $this->assertStringContainsString(
            url('/'),
            $content,
            'Le sitemap doit contenir l\'URL racine du site'
        );

        $this->assertStringNotContainsString(
            '/admin',
            $content,
            'Le sitemap ne doit pas exposer les routes /admin'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\/audit\/[0-9a-f\-]{36}/',
            $content,
            'Le sitemap ne doit pas contenir d\'URLs d\'audit avec UUID'
        );

        $this->assertStringNotContainsString(
            '/r/',
            $content,
            'Le sitemap ne doit pas contenir de liens magiques /r/'
        );

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml, 'Le sitemap.xml doit être du XML bien formé');
    }

    // -------------------------------------------------------------------------
    // 3. Page d'accueil : maquette « backend de votre produit » (décision de l'actionnaire
    //    du 2026-10-09). Le copy est porté par Home.jsx ; pas de keyword stuffing (§8).
    // -------------------------------------------------------------------------

    public function test_home_page_meta_targets_the_backend_positioning(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Home')
                ->where('meta.title', fn (string $title) => str_contains(mb_strtolower($title), 'backend'))
                ->missing('meta.keywords')
            );
    }

    // -------------------------------------------------------------------------
    // 4. Page prestations — meta cible création et hébergement
    // -------------------------------------------------------------------------

    public function test_services_page_meta_contains_creation_and_hebergement_keywords(): void
    {
        $this->get('/prestations')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Services')
                ->where('meta.keywords', fn (string $keywords) => str_contains(strtolower($keywords), 'création') &&
                    (
                        str_contains(strtolower($keywords), 'hébergement') ||
                        str_contains(strtolower($keywords), 'hebergement')
                    )
                )
            );
    }

    // -------------------------------------------------------------------------
    // 5. robots.txt — présence de la directive Sitemap:
    // -------------------------------------------------------------------------

    public function test_robots_txt_declares_sitemap_directive(): void
    {
        $robotsPath = public_path('robots.txt');

        $this->assertFileExists($robotsPath, 'robots.txt doit être présent dans public/');

        $content = file_get_contents($robotsPath);

        $this->assertStringContainsString(
            'Sitemap:',
            $content,
            'robots.txt doit déclarer la directive Sitemap:'
        );
    }

    // -------------------------------------------------------------------------
    // 6. JSON-LD Service — le HTML brut contient au moins un nœud Service parsable
    //    Requête sans header X-Inertia pour obtenir le HTML complet (app.blade.php)
    // -------------------------------------------------------------------------

    public function test_homepage_html_contains_parsable_json_ld_with_service_nodes(): void
    {
        // GET sans header X-Inertia → Inertia retourne la vue complète (app.blade.php)
        // Le JSON-LD est rendu côté serveur dans le <head> de app.blade.php.
        $graph = $this->jsonLdGraph($this->get('/'));

        $serviceNodes = array_values(array_filter(
            $graph,
            fn (array $node) => ($node['@type'] ?? null) === 'Service'
        ));

        $this->assertGreaterThanOrEqual(
            1,
            count($serviceNodes),
            'Le JSON-LD @graph doit contenir au moins un nœud @type Service'
        );

        $serviceNames = array_column($serviceNodes, 'name');

        $this->assertContains('Outils métier sur mesure', $serviceNames);

        // Vote du 2026-09-29 : l'ancien catalogue (audits, monitoring, hébergement à prix
        // affiché) ne doit plus être publié aux moteurs de recherche.
        $this->assertNotContains('Hébergement web managé', $serviceNames);
        foreach ($serviceNodes as $node) {
            $this->assertArrayNotHasKey('offers', $node, 'aucun prix du catalogue ne doit partir dans le JSON-LD');
        }
    }

    // -------------------------------------------------------------------------
    // 7. Smoke sweep — toutes les routes publiques sans paramètre répondent < 500
    // -------------------------------------------------------------------------

    public function test_all_public_routes_return_non_server_error_responses(): void
    {
        $publicRoutes = [
            '/',
            '/prestations',
            '/hebergement-web',
            '/contact',
            '/audit',
            '/mentions-legales',
            '/cgv',
            '/sitemap.xml',
        ];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $status = $response->getStatusCode();

            $this->assertLessThan(
                500,
                $status,
                "La route {$route} a retourné une erreur serveur (HTTP {$status})"
            );
        }
    }
}
