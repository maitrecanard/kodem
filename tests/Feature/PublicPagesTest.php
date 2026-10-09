<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\ReadsJsonLd;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use ReadsJsonLd;
    use RefreshDatabase;

    public function test_home_page_renders_the_backend_mockup(): void
    {
        // Décision de l'actionnaire du 2026-10-09 : l'accueil reprend la maquette « backend de
        // votre produit ». Son contenu vit dans Home.jsx, la page ne reçoit donc que ses meta.
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Home')
                ->where('meta.title', fn ($t) => str_contains(mb_strtolower($t), 'kodem')
                    && str_contains(mb_strtolower($t), 'backend'))
                ->missing('positioning')
                ->missing('cases')
                ->missing('testimonials')
            );
    }

    public function test_public_contact_address_is_the_single_mailbox(): void
    {
        $expected = 'mathieu.siaudeau@kodem.fr';

        $response = $this->get('/')->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('contactEmail', $expected));

        $organization = collect($this->jsonLdGraph($response))->firstWhere('@type', 'Organization');

        $this->assertSame($expected, $organization['email']);
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

    public function test_home_source_carries_the_mockup_copy(): void
    {
        // SSR inactif en test : le texte de l'accueil n'est vérifiable que dans la source JSX.
        $source = file_get_contents(resource_path('js/Pages/Public/Home.jsx'));

        $this->assertNotFalse($source);
        $this->assertStringContainsString('de votre produit.', $source);
        $this->assertStringContainsString('Trois façons de travailler ensemble.', $source);
        $this->assertStringContainsString("J'ai réparé la production avant de la coder.", $source);
        $this->assertSame(6, substr_count($source, "meta: '// compétence 0") + substr_count($source, 'meta: "// compétence 0'));
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
