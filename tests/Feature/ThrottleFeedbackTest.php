<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Une limite de débit atteinte renvoie une 429 brute qu'Inertia ne sait pas
 * interpréter : l'interface reste figée et l'utilisateur croit que le bouton est
 * cassé. Le handler de bootstrap/app.php la convertit, pour les seules requêtes
 * Inertia, en retour arrière porteur d'un message affichable — sans jamais
 * assouplir la limite elle-même.
 */
class ThrottleFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('audit');
        RateLimiter::clear('contact');
    }

    private function payload(): array
    {
        return ['url' => 'https://exemple.fr', 'type' => 'full'];
    }

    private function exhaustAuditLimit(): void
    {
        // La limite est de 3 audits par heure et par IP.
        for ($i = 0; $i < 3; $i++) {
            $this->post('/audit', $this->payload());
        }
    }

    public function test_an_inertia_request_gets_a_readable_message_instead_of_a_raw_429(): void
    {
        $this->exhaustAuditLimit();

        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/audit', $this->payload());

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertStringContainsString(
            'Trop de tentatives',
            (string) session('error'),
            'le message doit être compréhensible par un visiteur'
        );
    }

    /**
     * Garde-fou : la conversion ne doit PAS masquer la 429 aux clients qui la
     * comprennent (API, scripts, monitoring), qui ont besoin du code et de
     * l'en-tête Retry-After standard.
     */
    public function test_a_non_inertia_client_still_receives_a_real_429(): void
    {
        $this->exhaustAuditLimit();

        $response = $this->post('/audit', $this->payload());

        $response->assertStatus(429);
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    /**
     * Le plus important : la présentation change, la protection non. La 4e
     * requête ne doit créer aucun audit supplémentaire.
     */
    public function test_the_rate_limit_itself_is_still_enforced(): void
    {
        $this->exhaustAuditLimit();
        $countAfterLimit = \App\Models\Audit::count();

        $this->withHeaders(['X-Inertia' => 'true'])->post('/audit', $this->payload());

        $this->assertSame(
            $countAfterLimit,
            \App\Models\Audit::count(),
            'une requête bloquée ne doit jamais atteindre le contrôleur'
        );
    }

    /**
     * Le message peut bien être en session : sans la prop `flash` partagée par
     * HandleInertiaRequests, il n'atteint jamais le composant React et aucun
     * bandeau ne s'affiche.
     */
    public function test_the_flash_prop_is_shared_with_every_inertia_page(): void
    {
        $this->get('/audit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('flash'));
    }

    public function test_the_throttle_message_reaches_the_page_as_a_flash_prop(): void
    {
        $this->exhaustAuditLimit();

        $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/audit', $this->payload())
            ->assertRedirect();

        // withHeaders() persiste d'une requête à l'autre dans un test Laravel :
        // sans ce flush, le GET repartirait en mode Inertia (réponse JSON).
        $this->flushHeaders();

        // La requête suivante consomme le message de session : il doit arriver
        // au composant sous forme de prop, pas seulement exister en session.
        $this->get('/audit')->assertInertia(fn (Assert $page) => $page
            ->where('flash.error', fn ($error) => str_contains((string) $error, 'Trop de tentatives'))
        );
    }
}
