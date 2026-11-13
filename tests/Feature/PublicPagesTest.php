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

    public function test_pub_ia_page_renders_without_video_until_rights_are_cleared(): void
    {
        // Arbitrage du 2026-11-13 : la vidéo de démonstration n'est servie que si son fichier
        // est déployé, après l'enregistrement Stability AI et l'accord écrit de Localoge.
        $this->withPubIaVideo(false, fn () => $this->get('/pub-ia')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/PubIa')
                ->where('meta.title', fn ($t) => str_contains($t, 'IA') && str_contains($t, 'Kodem'))
                ->where('meta.description', fn ($d) => ! preg_match('/\\d+\\s?€/u', $d))
                ->where('video', null)
                ->where('flyers', [])
            ), withFlyers: false);
    }

    public function test_pub_ia_page_serves_the_demo_video_once_deployed(): void
    {
        $this->withPubIaVideo(true, fn () => $this->get('/pub-ia')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('video.src', '/media/pub-ia/demo-16x9.mp4')
            ));
    }

    /**
     * Exécute $test avec ou sans fichier vidéo, sans toucher durablement au fichier local
     * éventuellement présent (il n'est pas versionné).
     */
    private function withPubIaVideo(bool $present, callable $test, bool $withFlyers = true): void
    {
        $dossierFlyers = public_path('media/pub-ia');
        $flyersMasques = [];
        if (! $withFlyers && is_dir($dossierFlyers)) {
            foreach (glob($dossierFlyers.'/*.webp') as $flyer) {
                rename($flyer, $flyer.'.test-bak');
                $flyersMasques[] = $flyer;
            }
        }

        $fichier = public_path('media/pub-ia/demo-16x9.mp4');
        $sauvegarde = $fichier.'.test-bak';
        $existait = is_file($fichier);
        $dossierCree = false;

        if ($existait) {
            rename($fichier, $sauvegarde);
        }
        if ($present) {
            if (! is_dir(dirname($fichier))) {
                mkdir(dirname($fichier), 0775, true);
                $dossierCree = true;
            }
            file_put_contents($fichier, 'video');
        }

        try {
            $test();
        } finally {
            if ($present) {
                unlink($fichier);
            }
            if ($existait) {
                rename($sauvegarde, $fichier);
            }
            if ($dossierCree) {
                rmdir(dirname($fichier));
            }
            foreach ($flyersMasques as $flyer) {
                rename($flyer.'.test-bak', $flyer);
            }
        }
    }

    public function test_pub_ia_page_lists_only_deployed_flyers(): void
    {
        $dossier = public_path('media/pub-ia');
        $crees = [];
        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
            $crees[] = $dossier;
        }
        foreach (['flyer-france.webp', 'flyer-france-grand.webp'] as $nom) {
            if (! is_file($dossier.'/'.$nom)) {
                file_put_contents($dossier.'/'.$nom, 'webp');
                array_unshift($crees, $dossier.'/'.$nom);
            }
        }

        try {
            $this->get('/pub-ia')
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('flyers.0.src', '/media/pub-ia/flyer-france.webp')
                    ->where('flyers.0.grand', '/media/pub-ia/flyer-france-grand.webp')
                    ->where('flyers', fn ($flyers) => collect($flyers)->every(
                        fn ($f) => is_file(public_path(ltrim($f['src'], '/')))
                    ))
                );
        } finally {
            foreach ($crees as $chemin) {
                is_dir($chemin) ? rmdir($chemin) : unlink($chemin);
            }
        }
    }

    public function test_pub_ia_waitlist_goes_through_the_contact_form(): void
    {
        $this->from('/pub-ia')->post('/contact', [
            'name' => 'Hôte Test',
            'email' => 'hote@example.com',
            'company' => 'Gîte test',
            'subject' => 'Publicités IA : être prévenu du lancement',
            'message' => 'Je souhaite être prévenu du lancement des publicités vidéo et photo générées par IA.',
        ])->assertRedirect('/pub-ia');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'hote@example.com',
            'subject' => 'Publicités IA : être prévenu du lancement',
        ]);
    }
}
