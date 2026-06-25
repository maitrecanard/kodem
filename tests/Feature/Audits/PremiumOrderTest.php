<?php

namespace Tests\Feature\Audits;

use App\Modules\Audits\Models\AuditRequest;
use App\Modules\Audits\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\Checkout\Session as StripeSession;
use Tests\TestCase;

class PremiumOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_inertia_page_with_price_and_currency(): void
    {
        Config::set('audits.premium.price_cents', 14900);
        Config::set('audits.premium.currency', 'eur');

        $this->get(route('audits.premium'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Audits/Premium')
                ->where('price_cents', 14900)
                ->where('currency', 'eur')
            );
    }

    public function test_merci_page_renders(): void
    {
        $this->get(route('audits.merci'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Audits/Merci'));
    }

    public function test_annule_page_renders(): void
    {
        $this->get(route('audits.annule'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Audits/Annule'));
    }

    public function test_checkout_validates_required_fields(): void
    {
        $this->post(route('audits.premium.checkout'), [])
            ->assertSessionHasErrors(['name', 'email', 'domain', 'options', 'rgpd_consent', 'form_loaded_at']);
    }

    public function test_checkout_rejects_invalid_domain(): void
    {
        $this->post(route('audits.premium.checkout'), [
            'name' => 'Jean Test',
            'email' => 'jean@example.com',
            'domain' => 'not a domain',
            'options' => ['seo' => true, 'security' => true],
            'rgpd_consent' => true,
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ])->assertSessionHasErrors('domain');
    }

    public function test_checkout_rejects_when_no_option_selected(): void
    {
        $this->post(route('audits.premium.checkout'), [
            'name' => 'Jean Test',
            'email' => 'jean@example.com',
            'domain' => 'example.fr',
            'options' => ['seo' => false, 'security' => false],
            'rgpd_consent' => true,
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ])->assertSessionHasErrors('options');
    }

    public function test_checkout_rejects_extra_keys_in_options(): void
    {
        $this->post(route('audits.premium.checkout'), [
            'name' => 'Jean Test',
            'email' => 'jean@example.com',
            'domain' => 'example.fr',
            'options' => ['seo' => true, 'security' => true, 'malicious' => 'payload'],
            'rgpd_consent' => true,
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ])->assertSessionHasErrors('options');
    }

    public function test_checkout_rejects_form_filled_too_fast_as_bot(): void
    {
        $this->post(route('audits.premium.checkout'), [
            'name' => 'Jean Test',
            'email' => 'jean@gmail.com',
            'domain' => 'example.fr',
            'options' => ['seo' => true, 'security' => true],
            'rgpd_consent' => true,
            'form_loaded_at' => now()->timestamp, // 0 seconde écoulée
        ]);

        // Le rejet anti-bot renvoie back()->withErrors et NE crée PAS d'AuditRequest.
        $this->assertDatabaseCount('audit_requests', 0);
    }

    public function test_checkout_rejects_honeypot_filled(): void
    {
        $response = $this->post(route('audits.premium.checkout'), [
            'name' => 'Jean Test',
            'email' => 'jean@gmail.com',
            'domain' => 'example.fr',
            'options' => ['seo' => true, 'security' => true],
            'rgpd_consent' => true,
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
            'website' => 'bot-filled', // honeypot
        ]);

        // Le champ honeypot `website` a la règle `size:0` qui échoue à la validation.
        // La réponse est une redirection (302) et aucun AuditRequest n'est créé.
        $response->assertStatus(302);
        $this->assertDatabaseCount('audit_requests', 0);
    }

    public function test_checkout_creates_audit_request_and_returns_inertia_location(): void
    {
        // On mocke le service Stripe pour éviter de toucher le réseau
        $sessionStub = StripeSession::constructFrom(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/test/cs_test_123']);

        $mock = $this->createMock(StripeCheckoutService::class);
        $mock->expects($this->once())
            ->method('createSessionForAuditRequest')
            ->willReturn($sessionStub);

        $this->app->instance(StripeCheckoutService::class, $mock);

        // Le header X-Inertia est requis pour que Inertia::location renvoie 409
        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->post(route('audits.premium.checkout'), [
                'name' => 'Jean Test',
                'email' => 'jean@gmail.com',
                'domain' => 'example.fr',
                'options' => ['seo' => true, 'security' => true],
                'rgpd_consent' => true,
                'form_loaded_at' => now()->subSeconds(10)->timestamp,
            ]);

        // Inertia::location renvoie un 409 avec header X-Inertia-Location
        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/test/cs_test_123');

        $this->assertDatabaseCount('audit_requests', 1);
        $this->assertDatabaseHas('audit_requests', [
            'email' => 'jean@gmail.com',
            'domain' => 'example.fr',
            'status' => 'pending_payment',
        ]);
    }

    public function test_checkout_normalizes_domain(): void
    {
        $sessionStub = StripeSession::constructFrom(['id' => 'cs_x', 'url' => 'https://checkout.stripe.com/x']);

        $mock = $this->createMock(StripeCheckoutService::class);
        $mock->method('createSessionForAuditRequest')->willReturn($sessionStub);
        $this->app->instance(StripeCheckoutService::class, $mock);

        // Le domaine passe avec des majuscules : la règle regex est case-insensitive.
        // passedValidation() le normalise en minuscules.
        $this->post(route('audits.premium.checkout'), [
            'name' => 'Jean Test',
            'email' => 'jean@gmail.com',
            'domain' => 'Example.FR',
            'options' => ['seo' => true, 'security' => true],
            'rgpd_consent' => true,
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ]);

        $this->assertDatabaseHas('audit_requests', [
            'domain' => 'example.fr',
        ]);
    }
}
