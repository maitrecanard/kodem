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

    public function test_home_page_renders_the_business_tools_positioning(): void
    {
        // Vote du directeur du 2026-10-15 : l'accueil revient au positionnement « outils métier
        // sur mesure » et lit son texte dans content/positioning.json, comme les pages intérieures.
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Home')
                ->where('meta.title', fn ($t) => str_contains(mb_strtolower($t), 'kodem')
                    && str_contains(mb_strtolower($t), 'outils métier'))
                ->where('meta.description', fn ($d) => ! str_contains(mb_strtolower($d), 'paiement')
                    && ! str_contains(mb_strtolower($d), 'backend'))
                ->has('positioning.hero_baseline')
                ->has('positioning.cta_final')
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

    public function test_home_source_carries_the_business_tools_copy(): void
    {
        // SSR inactif en test : le texte de l'accueil n'est vérifiable que dans la source JSX.
        $source = file_get_contents(resource_path('js/Pages/Public/Home.jsx'));

        $this->assertNotFalse($source);
        $this->assertStringContainsString('Des outils métier sur mesure qui suppriment vos', $source);
        $this->assertStringContainsString("J'ai réparé la production avant de la coder.", $source);
        $this->assertSame(5, substr_count($source, "meta: '// compétence 0") + substr_count($source, 'meta: "// compétence 0'));

        // Vote du directeur du 2026-10-15 : ni paiement, ni reprise comme spécialité, ni preuve
        // hors de la liste fermée sur l'accueil.
        foreach ([
            'backend php', 'freelance', 'stripe', 'paiement', 'marketplace', "backend d'une app mobile",
            'reprendre une application', 'saas santé', 'équipe de 10', 'abonnements et factures',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, mb_strtolower($source), "l'accueil ne doit plus porter « {$forbidden} »");
        }
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
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Mentions')
                ->where('meta.description', fn ($d) => str_contains($d, 'entrepreneur individuel')
                    && ! str_contains(mb_strtolower($d), 'société'))
            );
    }

    public function test_mentions_legales_source_names_the_individual_entrepreneur_and_the_real_host(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Public/Mentions.jsx'));

        $this->assertNotFalse($source);
        $this->assertStringContainsString('entrepreneur individuel', $source);
        $this->assertStringContainsString('Directeur de la publication : Mathieu Siaudeau', $source);
        $this->assertStringContainsString('SIREN : 922 818 547', $source);
        $this->assertStringContainsString('OVH SAS', $source);
        $this->assertStringContainsString('2 rue Kellermann, 59100 Roubaix', $source);
        $this->assertStringNotContainsString('la société', $source);
        $this->assertStringNotContainsString('hébergé par Kodem', $source);
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
