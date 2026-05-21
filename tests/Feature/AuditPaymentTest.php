<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Tests\TestCase;

class AuditPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_renders_for_unpaid_audit(): void
    {
        $audit = Audit::create([
            'url' => 'https://shop.example',
            'status' => 'completed',
            'score_total' => 75,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('a', 64),
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditCheckout')
                ->where('audit.uuid', $audit->uuid)
                ->where('price.cents', 2900)
                ->where('price.label', '29,00 €')
                ->where('driver', 'stub')
            );
    }

    public function test_paid_audit_redirects_away_from_checkout(): void
    {
        $audit = Audit::create([
            'url' => 'https://paid.example',
            'status' => 'completed',
            'score_total' => 80,
            'price_cents' => 2900,
            'paid_at' => now(),
            'payment_reference' => 'STUB-DEJAPAYE',
            'ip_hash' => str_repeat('b', 64),
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay')
            ->assertRedirect(route('audit.show', $audit->uuid));
    }

    public function test_stub_payment_marks_audit_as_paid(): void
    {
        $audit = Audit::create([
            'url' => 'https://buy.example',
            'status' => 'completed',
            'score_total' => 60,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('c', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])
            ->assertRedirect(route('audit.show', $audit->uuid));

        $audit->refresh();
        $this->assertNotNull($audit->paid_at);
        $this->assertStringStartsWith('STUB-', $audit->payment_reference);
    }

    public function test_stub_payment_requires_confirmation(): void
    {
        $audit = Audit::create([
            'url' => 'https://forgot.example',
            'status' => 'completed',
            'score_total' => 60,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('d', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', [])
            ->assertSessionHasErrors('confirm');

        $audit->refresh();
        $this->assertNull($audit->paid_at);
    }

    public function test_already_paid_audit_cannot_be_paid_again(): void
    {
        $audit = Audit::create([
            'url' => 'https://double.example',
            'status' => 'completed',
            'score_total' => 70,
            'price_cents' => 2900,
            'paid_at' => now(),
            'payment_reference' => 'STUB-ORIGINAL',
            'ip_hash' => str_repeat('e', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])
            ->assertRedirect(route('audit.show', $audit->uuid));

        $audit->refresh();
        $this->assertSame('STUB-ORIGINAL', $audit->payment_reference);
    }

    public function test_stripe_checkout_requires_an_email(): void
    {
        config(['audit.payment_driver' => 'stripe']);
        $this->fakeStripe();

        $audit = $this->makeAudit('https://noemail.example');

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])
            ->assertSessionHasErrors('email');

        $audit->refresh();
        $this->assertNull($audit->paid_at);
        $this->assertNull($audit->payment_token);
    }

    public function test_stripe_checkout_creates_session_and_stores_private_token(): void
    {
        config(['audit.payment_driver' => 'stripe']);
        $this->fakeStripe();

        $audit = $this->makeAudit('https://buy-stripe.example');

        $response = $this->post('/audit/'.$audit->uuid.'/pay', [
            'email' => 'client@example.com',
            'confirm' => '1',
        ]);

        // Inertia::location renvoie une redirection (302) ou un 409 selon le contexte.
        $this->assertContains($response->getStatusCode(), [302, 409]);

        $audit->refresh();
        $this->assertSame('client@example.com', $audit->email);
        $this->assertNotNull($audit->payment_token);
        $this->assertSame('cs_test_123', $audit->stripe_session_id);
        $this->assertNull($audit->paid_at, 'Audit must not be paid before Stripe confirmation.');
    }

    public function test_confirm_marks_audit_paid_when_stripe_session_is_paid(): void
    {
        config(['audit.payment_driver' => 'stripe']);
        $this->fakeStripe('paid');

        $audit = $this->makeAudit('https://confirm.example');
        $audit->update([
            'email' => 'client@example.com',
            'payment_token' => 'secret-token-abc',
            'stripe_session_id' => 'cs_test_123',
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay/confirm?token=secret-token-abc')
            ->assertRedirect(route('audit.show', $audit->uuid));

        $audit->refresh();
        $this->assertNotNull($audit->paid_at);
        $this->assertSame('pi_test_123', $audit->payment_reference);
        $this->assertNull($audit->payment_token, 'Token must be consumed after confirmation.');
    }

    public function test_confirm_rejects_an_invalid_token(): void
    {
        config(['audit.payment_driver' => 'stripe']);
        $this->fakeStripe('paid');

        $audit = $this->makeAudit('https://badtoken.example');
        $audit->update([
            'payment_token' => 'real-token',
            'stripe_session_id' => 'cs_test_123',
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay/confirm?token=forged-token')
            ->assertForbidden();

        $audit->refresh();
        $this->assertNull($audit->paid_at);
    }

    public function test_confirm_does_not_mark_paid_when_stripe_session_unpaid(): void
    {
        config(['audit.payment_driver' => 'stripe']);
        $this->fakeStripe('unpaid');

        $audit = $this->makeAudit('https://unpaid.example');
        $audit->update([
            'payment_token' => 'real-token',
            'stripe_session_id' => 'cs_test_123',
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay/confirm?token=real-token')
            ->assertRedirect(route('audit.pay', $audit->uuid));

        $audit->refresh();
        $this->assertNull($audit->paid_at);
    }

    private function makeAudit(string $url): Audit
    {
        return Audit::create([
            'url' => $url,
            'status' => 'completed',
            'score_total' => 70,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('f', 64),
        ]);
    }

    /**
     * Remplace StripeCheckoutService par un faux afin de ne jamais appeler l'API.
     */
    private function fakeStripe(string $paymentStatus = 'paid'): void
    {
        $fake = new class($paymentStatus) extends StripeCheckoutService {
            public function __construct(private string $paymentStatus = 'paid')
            {
                // On n'appelle pas le constructeur parent (pas de StripeClient réel).
            }

            public function createAuditSession(Audit $audit, string $email, string $token): Session
            {
                return Session::constructFrom([
                    'id' => 'cs_test_123',
                    'url' => 'https://checkout.stripe.test/session',
                ]);
            }

            public function retrieveSession(string $sessionId): Session
            {
                return Session::constructFrom([
                    'id' => $sessionId,
                    'payment_status' => $this->paymentStatus,
                    'payment_intent' => 'pi_test_123',
                ]);
            }
        };

        $this->app->instance(StripeCheckoutService::class, $fake);
    }
}
