<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_niche_positioning(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Home')
                ->where('meta.title', fn ($t) => str_contains(strtolower($t), 'kodem')
                    && str_contains(strtolower($t), 'dispositifs connectés'))
                // Positionnement niche servi depuis content/positioning.json (§0)
                ->where('positioning.hero_title', fn ($h) => str_contains(strtolower($h), 'dispositifs connectés'))
                ->has('positioning.secteurs')
                ->has('cases')
                ->has('testimonials')
            );
    }

    public function test_home_exposes_the_five_real_testimonials_without_rating(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Home')
                ->has('testimonials', 5)
                ->where('testimonials', function ($testimonials) {
                    foreach ($testimonials as $t) {
                        $this->assertNotEmpty($t['nom']);
                        $this->assertNotSame('Prénom Nom', $t['nom']);
                        $this->assertArrayNotHasKey('rating', $t);
                        $this->assertArrayNotHasKey('exemple', $t);
                    }

                    return true;
                })
            );
    }

    public function test_home_html_never_emits_self_review_schema(): void
    {
        // Le HTML de l'accueil ne doit jamais porter de schema.org Review/AggregateRating (§4).
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('AggregateRating', $html);
        $this->assertStringNotContainsString('aggregateRating', $html);
        $this->assertStringNotContainsString('ratingValue', $html);
        $this->assertStringNotContainsString('Review', $html);
    }

    public function test_home_html_carries_the_imported_testimonial_authors(): void
    {
        // Preuve positive que les témoignages réels partent bien au client (SSR inactif en test :
        // seul le JSON Inertia est présent dans le HTML, encodé/échappé par Blade).
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('MUXEN', $html);
        $this->assertStringContainsString('Keddy Andamba', $html);
        $this->assertStringContainsString('Sophie Douezy', $html);
    }

    public function test_services_page_renders(): void
    {
        $this->get('/prestations')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Services')
                ->has('prestations', fn ($p) => $p->etc())
            );
    }

    public function test_contact_page_renders(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Contact'));
    }

    public function test_mentions_legales_page_renders(): void
    {
        $this->get('/mentions-legales')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Mentions'));
    }

    public function test_cgv_page_renders(): void
    {
        $this->get('/cgv')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Cgv'));
    }

    public function test_audit_page_renders_with_form(): void
    {
        $this->get('/audit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Audit')
                ->has('paidPrestations')
            );
    }
}
