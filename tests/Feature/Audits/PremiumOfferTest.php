<?php

namespace Tests\Feature\Audits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Le rapport SEO complet est la SEULE offre payante : il est rédigé
 * manuellement, coûte 150 € et est transmis sous 24 à 48 h. L'audit en ligne
 * n'est plus qu'un aperçu gratuit.
 */
class PremiumOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_premium_page_is_reachable_and_states_its_terms(): void
    {
        $this->get('/audits/premium')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Audits/Premium')
                ->where('price_cents', 15000)
                ->where('delivery_hours', '24 à 48 h')
                ->where('manual', true)
                ->where('audience', 'professionnels')
                // Franchise en base : la mention de l'article 293 B est obligatoire
                // sur tout document commercial.
                ->where('vat_mention', 'TVA non applicable, art. 293 B du CGI')
            );
    }

    /**
     * Le prix doit venir de la configuration, jamais être codé en dur dans une
     * vue : un écart entre l'affichage et le montant encaissé par Stripe serait
     * une erreur de facturation.
     */
    public function test_the_configured_price_is_150_euros(): void
    {
        $this->assertSame(15000, (int) config('audits.premium.price_cents'));
        $this->assertSame('eur', config('audits.premium.currency'));
    }

    public function test_no_vat_is_added_on_top_of_the_displayed_price(): void
    {
        $this->assertFalse(config('audits.vat.applicable'));
        $this->assertSame(0.0, (float) config('audits.vat.rate_percent'));
    }

    // -------------------------------------------------------------------------
    // Retrait de l'ancienne offre automatique à 29 €
    // -------------------------------------------------------------------------

    public function test_the_audit_page_no_longer_advertises_the_29_euro_report(): void
    {
        $response = $this->get('/audit');
        $response->assertOk();

        $html = $response->getContent();

        // Les accents sont échappés en \uXXXX dans le payload Inertia : on
        // n'assère que sur des motifs ASCII. « 29 » seul est trop large (il
        // apparaît dans les empreintes de fichiers d'assets) : on vise le prix
        // formaté et le montant en centimes.
        $this->assertStringNotContainsString('29,00', $html);
        $this->assertStringNotContainsString('price_cents\":2900', $html);
    }

    public function test_the_audit_page_exposes_the_premium_offer_instead(): void
    {
        $this->get('/audit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Audit')
                ->where('premium.price_label', '150 €')
                ->where('premium.url', '/audits/premium')
                ->missing('price')
            );
    }
}
