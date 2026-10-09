<?php

namespace Tests\Feature;

use App\Services\VitrineContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ReadsJsonLd;
use Tests\TestCase;

class VitrineTest extends TestCase
{
    use ReadsJsonLd;
    use RefreshDatabase;

    private const PUBLISHED_CASE_SLUGS = ['muxen', 'freendzy', 'ecran-raspberry', 'carte-qr-manhattan-cafe'];

    // -------------------------------------------------------------------------
    // GET /realisations
    // -------------------------------------------------------------------------

    public function test_realisations_index_returns_200_with_correct_component_and_non_empty_meta(): void
    {
        $this->get('/realisations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Realisations')
                ->where('meta.title', fn (string $title) => strlen($title) > 0)
                ->where('meta.description', fn (string $desc) => strlen($desc) > 0)
            );
    }

    // -------------------------------------------------------------------------
    // GET /realisations/photomaton
    // -------------------------------------------------------------------------

    public function test_realisation_photomaton_returns_200_with_cas_prop_and_non_empty_meta(): void
    {
        $this->get('/realisations/photomaton')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/RealisationShow')
                ->has('cas')
                ->where('meta.title', fn (string $title) => strlen($title) > 0)
                ->where('meta.description', fn (string $desc) => strlen($desc) > 0)
            );
    }

    // -------------------------------------------------------------------------
    // GET /realisations/ecran-raspberry
    // -------------------------------------------------------------------------

    public function test_realisation_ecran_raspberry_returns_200_with_correct_component(): void
    {
        $this->get('/realisations/ecran-raspberry')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/RealisationShow')
                ->has('cas')
            );
    }

    // -------------------------------------------------------------------------
    // GET /realisations/inexistant-xyz → 404
    // -------------------------------------------------------------------------

    public function test_realisation_inexistant_returns_404(): void
    {
        $this->get('/realisations/inexistant-xyz')->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // GET /expertises
    // -------------------------------------------------------------------------

    public function test_expertises_returns_200_with_correct_component_and_non_empty_meta(): void
    {
        $this->get('/expertises')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Expertises')
                ->where('meta.title', fn (string $title) => strlen($title) > 0)
                ->where('meta.description', fn (string $desc) => strlen($desc) > 0)
            );
    }

    // -------------------------------------------------------------------------
    // GET /zone-intervention
    // -------------------------------------------------------------------------

    public function test_zone_intervention_returns_200_with_correct_component_and_non_empty_meta(): void
    {
        $this->get('/zone-intervention')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ZoneIntervention')
                ->where('meta.title', fn (string $title) => strlen($title) > 0)
                ->where('meta.description', fn (string $desc) => strlen($desc) > 0)
            );
    }

    // -------------------------------------------------------------------------
    // GET /notes
    // -------------------------------------------------------------------------

    public function test_notes_returns_200_with_correct_component_and_non_empty_meta(): void
    {
        $this->get('/notes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Notes')
                ->where('meta.title', fn (string $title) => strlen($title) > 0)
                ->where('meta.description', fn (string $desc) => strlen($desc) > 0)
            );
    }

    // -------------------------------------------------------------------------
    // Detail pages: jsonLd prop must contain a BreadcrumbList node
    // -------------------------------------------------------------------------

    public function test_detail_page_json_ld_prop_contains_breadcrumb_list_node(): void
    {
        $pages = [
            '/realisations',
            '/realisations/muxen',
            '/realisations/freendzy',
            '/realisations/photomaton',
            '/realisations/ecran-raspberry',
            '/expertises',
            '/zone-intervention',
            '/notes',
        ];

        foreach ($pages as $route) {
            $this->get($route)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('jsonLd', fn ($jsonLd) => collect($jsonLd)->contains('@type', 'BreadcrumbList')
                    )
                );
        }
    }

    // -------------------------------------------------------------------------
    // Testimonials hidden when empty (§ spec)
    // -------------------------------------------------------------------------

    public function test_photomaton_testimonials_prop_is_empty_array(): void
    {
        $this->get('/realisations/photomaton')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/RealisationShow')
                ->where('testimonials', [])
            );
    }

    // -------------------------------------------------------------------------
    // Case images: real path for the illustrated cases, TODO sentinel for the rest
    // -------------------------------------------------------------------------

    public function test_case_pages_expose_the_expected_image_or_sentinel(): void
    {
        $expectedImages = [
            'muxen' => null,
            'freendzy' => null,
            'ecran-raspberry' => null,
            'carte-qr-manhattan-cafe' => '/images/realisations/carte-qr-manhattan-cafe.webp',
            'photomaton' => '/images/realisations/photomaton.webp',
            'controle-acces-billetterie' => '/images/realisations/controle-acces-billetterie.webp',
        ];

        foreach ($expectedImages as $slug => $expectedImage) {
            $this->get("/realisations/{$slug}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Public/RealisationShow')
                    ->where('cas.image', fn (string $image) => $expectedImage === null
                        ? str_starts_with($image, '// TODO')
                        : $image === $expectedImage)
                );
        }

        // Le jeu ci-dessus doit couvrir TOUS les cas : un cas ajouté sans être
        // déclaré ici doit faire échouer ce test plutôt que passer inaperçu.
        $this->assertEqualsCanonicalizing(
            array_keys($expectedImages),
            array_column(VitrineContent::cases(), 'slug'),
            'tout cas doit être couvert par ce test (visuel réel ou sentinelle attendus)'
        );
    }

    public function test_realisations_index_lists_the_four_published_cases_in_order(): void
    {
        $this->get('/realisations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Realisations')
                ->where('cases', fn ($cases) => collect($cases)->pluck('slug')->all() === [
                    'muxen',
                    'freendzy',
                    'ecran-raspberry',
                    'carte-qr-manhattan-cafe',
                ])
            );
    }

    public function test_archived_cases_stay_reachable_but_are_not_indexed(): void
    {
        foreach (['/realisations/photomaton', '/realisations/controle-acces-billetterie'] as $route) {
            $this->get($route)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('meta.robots', 'noindex, follow'));
        }

        $this->get('/realisations/muxen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('meta.robots'));
    }

    public function test_notes_stay_reachable_but_are_not_indexed(): void
    {
        foreach (['/notes', '/notes/borne-evenement-sans-internet'] as $route) {
            $this->get($route)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('meta.robots', 'noindex, follow'));
        }
    }

    public function test_case_testimonials_are_linked_to_their_case(): void
    {
        $this->get('/realisations/muxen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('testimonials', 1)
                ->where('testimonials.0.nom', 'William')
            );

        $this->get('/realisations/freendzy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('testimonials', 1));
    }

    public function test_expertises_faq_json_ld_is_built_from_the_positioning_faq(): void
    {
        $faq = VitrineContent::positioning()['faq'];

        $this->get('/expertises')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('jsonLd', function ($jsonLd) use ($faq) {
                    $faqPage = collect($jsonLd)->firstWhere('@type', 'FAQPage');

                    $this->assertNotNull($faqPage, 'la page doit porter un nœud FAQPage');
                    $this->assertCount(count($faq), $faqPage['mainEntity']);
                    $this->assertSame($faq[0]['question'], $faqPage['mainEntity'][0]['name']);

                    return true;
                })
            );
    }

    public function test_sitemap_lists_only_published_cases_and_hides_notes(): void
    {
        $content = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (self::PUBLISHED_CASE_SLUGS as $slug) {
            $this->assertStringContainsString('<loc>'.url('/realisations/'.$slug).'</loc>', $content, "le cas publié '{$slug}' doit figurer au sitemap");
        }

        foreach (['photomaton', 'controle-acces-billetterie'] as $slug) {
            $this->assertStringNotContainsString('<loc>'.url('/realisations/'.$slug).'</loc>', $content, "le cas archivé '{$slug}' ne doit pas figurer au sitemap");
        }

        $this->assertStringNotContainsString(url('/notes'), $content, 'les notes ne doivent pas figurer au sitemap');
    }

    public function test_published_case_pages_serve_meta_that_fits_search_snippets(): void
    {
        foreach (self::PUBLISHED_CASE_SLUGS as $slug) {
            $this->get('/realisations/'.$slug)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('meta.title', function (string $title) use ($slug) {
                        $this->assertLessThanOrEqual(60, mb_strlen($title), "le titre du cas '{$slug}' dépasse 60 caractères");

                        return true;
                    })
                    ->where('meta.description', function (string $description) use ($slug) {
                        $this->assertLessThanOrEqual(160, mb_strlen($description), "le résumé du cas '{$slug}' dépasse 160 caractères");

                        return true;
                    })
                );
        }
    }

    public function test_case_pages_do_not_leak_home_testimonials(): void
    {
        foreach (['/realisations/photomaton', '/realisations/carte-qr-manhattan-cafe'] as $route) {
            $html = $this->get($route)->assertOk()->getContent();

            $this->assertStringNotContainsString('Keddy Andamba', $html);
            $this->assertStringNotContainsString('MUXEN', $html);
        }
    }

    // -------------------------------------------------------------------------
    // JSON-LD safety: no self-review schema (§4) + parseable ld+json block
    //
    // Requests without X-Inertia header return the full Blade HTML,
    // which includes the <script type="application/ld+json"> block rendered
    // server-side in app.blade.php.
    // -------------------------------------------------------------------------

    public function test_new_pages_html_do_not_contain_self_review_schema_and_have_parseable_json_ld(): void
    {
        $pages = [
            '/realisations',
            '/realisations/muxen',
            '/realisations/freendzy',
            '/realisations/photomaton',
            '/realisations/ecran-raspberry',
            '/expertises',
            '/zone-intervention',
            '/notes',
        ];

        foreach ($pages as $route) {
            $response = $this->get($route);
            $html = $response->getContent();

            // § Safety — no review-inflation schema
            $this->assertStringNotContainsString(
                'AggregateRating',
                $html,
                "{$route} ne doit pas émettre de nœud AggregateRating"
            );

            // '"Review"' — with JSON_HEX_QUOT the structural " stays; the type value
            // "Review" would still contain the substring Review. We assert neither the
            // encoded form "Review" nor any Review keyword appears.
            $this->assertStringNotContainsString(
                'Review',
                $html,
                "{$route} ne doit pas émettre de nœud Review"
            );

            // § Parseable ld+json block must be present
            $this->jsonLdGraph($response);
        }
    }

    // -------------------------------------------------------------------------
    // National coverage: meta must announce "France" without pinning the
    // promise to a single region, except on /zone-intervention which carries
    // the graduated promise (jour même en Nouvelle-Aquitaine, 48 h ailleurs).
    // -------------------------------------------------------------------------

    public function test_public_pages_meta_announce_national_coverage(): void
    {
        $pages = ['/', '/contact', '/expertises'];

        foreach ($pages as $route) {
            $title = null;
            $description = null;

            $this->get($route)
                ->assertOk()
                ->assertInertia(function (Assert $page) use (&$title, &$description) {
                    $page
                        ->where('meta.title', function (string $t) use (&$title) {
                            $title = $t;

                            return true;
                        })
                        ->where('meta.description', function (string $d) use (&$description) {
                            $description = $d;

                            return true;
                        });
                });

            $this->assertStringNotContainsString('Nouvelle-Aquitaine', $title, "{$route} : le title ne doit pas mentionner la Nouvelle-Aquitaine");
            $this->assertStringNotContainsString('Nouvelle-Aquitaine', $description, "{$route} : la description ne doit pas mentionner la Nouvelle-Aquitaine");
            $this->assertStringContainsString('France', $title.' '.$description, "{$route} : title + description doivent mentionner la France");
        }
    }

    public function test_zone_intervention_meta_keeps_the_poitiers_base_and_graduated_promise(): void
    {
        $this->get('/zone-intervention')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.title', function (string $title) {
                    $this->assertStringContainsString('Poitiers', $title);
                    $this->assertStringNotContainsString('Nouvelle-Aquitaine', $title, 'le title ne doit pas porter la promesse graduée : elle vit dans la description');

                    return true;
                })
                ->where('meta.description', function (string $description) {
                    $this->assertStringContainsString('Poitiers', $description);
                    $this->assertStringContainsString('jour même', $description);
                    $this->assertStringContainsString('Nouvelle-Aquitaine', $description);
                    $this->assertStringContainsString('48 h', $description);

                    return true;
                })
            );
    }

    public function test_local_business_json_ld_serves_the_whole_of_france(): void
    {
        $graph = collect($this->jsonLdGraph($this->get('/zone-intervention')->assertOk()));

        $localBusiness = $graph->firstWhere('@type', 'LocalBusiness');
        $this->assertNotNull($localBusiness, 'le graphe doit contenir un nœud LocalBusiness');

        $expectedAreaServed = ['@type' => 'Country', 'name' => 'France'];

        $this->assertSame($expectedAreaServed, $localBusiness['areaServed']);
        $this->assertSame('Poitiers', $localBusiness['address']['addressLocality']);
        $this->assertSame('Nouvelle-Aquitaine', $localBusiness['address']['addressRegion']);
        $this->assertSame('46.5802', $localBusiness['geo']['latitude']);

        foreach ($graph as $node) {
            if (isset($node['areaServed'])) {
                $this->assertNotSame(
                    'Nouvelle-Aquitaine',
                    $node['areaServed'],
                    "le nœud '{$node['@type']}' ne doit plus déclarer une zone desservie régionale"
                );
            }
        }

        $organization = $graph->firstWhere('@type', 'Organization');
        $service = $graph->firstWhere('@type', 'Service');

        $this->assertNotNull($organization, 'le graphe doit contenir un nœud Organization');
        $this->assertNotNull($service, 'le graphe doit contenir au moins un nœud Service');

        $this->assertSame($expectedAreaServed, $organization['areaServed']);
        $this->assertSame($expectedAreaServed, $service['areaServed']);
    }

    // -------------------------------------------------------------------------
    // Regression: pre-existing public routes must not regress to 5xx
    // -------------------------------------------------------------------------

    public function test_regression_existing_routes_return_non_server_error(): void
    {
        $routes = [
            '/',
            '/prestations',
            '/hebergement-web',
            '/contact',
            '/audit',
            '/sitemap.xml',
        ];

        foreach ($routes as $route) {
            $status = $this->get($route)->getStatusCode();

            $this->assertLessThan(
                500,
                $status,
                "Route existante {$route} a retourné une erreur serveur (HTTP {$status})"
            );
        }
    }
}
