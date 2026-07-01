<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VitrineTest extends TestCase
{
    use RefreshDatabase;

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
                    ->where('jsonLd', fn ($jsonLd) =>
                        collect($jsonLd)->contains('@type', 'BreadcrumbList')
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
            '/realisations/photomaton',
            '/realisations/ecran-raspberry',
            '/expertises',
            '/zone-intervention',
            '/notes',
        ];

        foreach ($pages as $route) {
            $html = $this->get($route)->getContent();

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
            $matched = preg_match(
                '/<script\s+type="application\/ld\+json">(.*?)<\/script>/s',
                $html,
                $matches
            );

            $this->assertSame(
                1,
                $matched,
                "{$route} doit contenir un bloc <script type=\"application/ld+json\">"
            );

            $data = json_decode($matches[1], true);

            $this->assertNotNull(
                $data,
                "{$route} : le contenu ld+json doit être du JSON valide"
            );
        }
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
