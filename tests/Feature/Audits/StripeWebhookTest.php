<?php

namespace Tests\Feature\Audits;

use App\Modules\Audits\Mail\PremiumOrderConfirmation;
use App\Modules\Audits\Models\AuditRequest;
use App\Modules\Audits\Notifications\NewPremiumOrderForAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => $this->webhookSecret]);
    }

    private function makeToken(): string
    {
        return Str::random(64);
    }

    private function createPendingAudit(array $attributes = []): AuditRequest
    {
        return AuditRequest::create(array_merge([
            'name'         => 'Jean Test',
            'email'        => 'jean@example.com',
            'domain'       => 'example.fr',
            'type'         => 'premium',
            'status'       => 'pending_payment',
            'options'      => ['seo' => true, 'security' => true],
            'access_token' => $this->makeToken(),
        ], $attributes));
    }

    private function signedWebhookRequest(string $payload): array
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return [
            'signature' => "t={$timestamp},v1={$signature}",
            'payload'   => $payload,
        ];
    }

    private function postWebhook(string $payload, string $signature): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            ['HTTP_Stripe-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );
    }

    private function checkoutCompletedPayload(array $metadata, string $paymentIntent = 'pi_test_123', int $amountTotal = 14900, string $currency = 'eur'): string
    {
        return json_encode([
            'id'   => 'evt_' . Str::random(10),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'object'         => 'checkout.session',
                    'id'             => 'cs_test_' . Str::random(10),
                    'payment_intent' => $paymentIntent,
                    'amount_total'   => $amountTotal,
                    'currency'       => $currency,
                    'metadata'       => $metadata,
                ],
            ],
        ]);
    }

    private function checkoutExpiredPayload(array $metadata): string
    {
        return json_encode([
            'id'   => 'evt_' . Str::random(10),
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'object'   => 'checkout.session',
                    'id'       => 'cs_test_' . Str::random(10),
                    'metadata' => $metadata,
                ],
            ],
        ]);
    }

    private function chargeRefundedPayload(string $paymentIntent): string
    {
        return json_encode([
            'id'   => 'evt_' . Str::random(10),
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'object'         => 'charge',
                    'id'             => 'ch_test_' . Str::random(10),
                    'payment_intent' => $paymentIntent,
                ],
            ],
        ]);
    }

    // Test 1: invalid/missing signature → 400, DB unchanged
    public function test_invalid_signature_returns_400(): void
    {
        $audit = $this->createPendingAudit();
        $payload = $this->checkoutCompletedPayload(['audit_request_id' => $audit->getKey()]);

        $this->postWebhook($payload, 't=12345,v1=invalidsignature')
            ->assertStatus(400);

        $this->assertDatabaseHas('audit_requests', [
            'id'     => $audit->getKey(),
            'status' => 'pending_payment',
        ]);
    }

    // Test 2: invalid/malformed payload → 400
    public function test_malformed_payload_returns_400(): void
    {
        $payload = 'not-valid-json{{{';
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        $header = "t={$timestamp},v1={$signature}";

        $this->postWebhook($payload, $header)
            ->assertStatus(400);
    }

    // Test 3: checkout.session.completed sets status queued, paid_at, mails
    public function test_checkout_completed_sets_queued_and_sends_mail(): void
    {
        Mail::fake();
        Notification::fake();

        $audit = $this->createPendingAudit();
        $payload = $this->checkoutCompletedPayload(
            ['audit_request_id' => (string) $audit->getKey()],
            'pi_test_abc',
            14900,
            'eur'
        );
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id'                    => $audit->getKey(),
            'status'                => 'queued',
            'stripe_payment_intent' => 'pi_test_abc',
            'amount_cents'          => 14900,
            'currency'              => 'eur',
        ]);

        $fresh = $audit->fresh();
        $this->assertNotNull($fresh->paid_at);

        // ENVOI SYNCHRONE : mis en file, ces messages ne partaient jamais sans un
        // worker `queue:work` en fonctionnement — le client payait sans rien
        // recevoir et personne n'était alerté. assertSent (et non assertQueued)
        // verrouille ce comportement.
        Mail::assertSent(PremiumOrderConfirmation::class, fn ($mail) => $mail->hasTo($audit->email));
        Mail::assertNotQueued(PremiumOrderConfirmation::class);
        Notification::assertSentOnDemand(NewPremiumOrderForAdmin::class);
    }

    public function test_the_internal_notification_goes_to_the_configured_admin_address(): void
    {
        Mail::fake();
        Notification::fake();

        config(['audits.admin_email' => 'mathieu.siaudeau@kodem.fr']);

        $audit = $this->createPendingAudit();
        $signed = $this->signedWebhookRequest($this->checkoutCompletedPayload(
            ['audit_request_id' => (string) $audit->getKey()]
        ));

        $this->postWebhook($signed['payload'], $signed['signature'])->assertStatus(200);

        Notification::assertSentOnDemand(
            NewPremiumOrderForAdmin::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'mathieu.siaudeau@kodem.fr'
        );
    }

    /**
     * Sans adresse admin configurée, la commande passe quand même : on ne perd
     * pas un paiement pour un défaut de configuration, mais l'incident est tracé.
     */
    public function test_a_missing_admin_address_is_logged_without_breaking_the_payment(): void
    {
        Mail::fake();
        Notification::fake();
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        config(['audits.admin_email' => null]);

        $audit = $this->createPendingAudit();
        $signed = $this->signedWebhookRequest($this->checkoutCompletedPayload(
            ['audit_request_id' => (string) $audit->getKey()]
        ));

        $this->postWebhook($signed['payload'], $signed['signature'])->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id' => $audit->getKey(),
            'status' => 'queued',
        ]);
        Notification::assertNothingSent();
    }

    // Test 4: idempotent — same event twice sends mail exactly once
    public function test_checkout_completed_is_idempotent(): void
    {
        Mail::fake();
        Notification::fake();

        $audit = $this->createPendingAudit();
        $payload = $this->checkoutCompletedPayload(
            ['audit_request_id' => (string) $audit->getKey()]
        );

        $signed = $this->signedWebhookRequest($payload);
        $this->postWebhook($signed['payload'], $signed['signature'])->assertStatus(200);

        // Second call — must produce a new valid signature with a fresh timestamp
        $signed2 = $this->signedWebhookRequest($payload);
        $this->postWebhook($signed2['payload'], $signed2['signature'])->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id'     => $audit->getKey(),
            'status' => 'queued',
        ]);

        // Envoi synchrone : l'unicité se vérifie sur assertSent, pas assertQueued.
        Mail::assertSent(PremiumOrderConfirmation::class, 1);
    }

    // Test 5: completed without metadata audit_request_id → 200, no DB change, no mail
    public function test_checkout_completed_without_audit_request_id_ignores_event(): void
    {
        Mail::fake();

        $payload = $this->checkoutCompletedPayload([]);
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        Mail::assertNothingQueued();
    }

    // Test 6: completed with unknown audit id → 200, no mail
    public function test_checkout_completed_with_unknown_audit_id_does_nothing(): void
    {
        Mail::fake();

        $payload = $this->checkoutCompletedPayload(['audit_request_id' => '99999']);
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        Mail::assertNothingQueued();
    }

    // Test 7: checkout.session.expired on pending_payment → status 'failed'
    public function test_checkout_expired_sets_failed_for_pending_payment(): void
    {
        $audit = $this->createPendingAudit();
        $payload = $this->checkoutExpiredPayload(['audit_request_id' => (string) $audit->getKey()]);
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id'     => $audit->getKey(),
            'status' => 'failed',
        ]);
    }

    // Test 8: expired ignores already-paid (queued) → stays 'queued'
    public function test_checkout_expired_ignores_already_paid(): void
    {
        $audit = $this->createPendingAudit(['status' => 'queued', 'paid_at' => now()]);
        $payload = $this->checkoutExpiredPayload(['audit_request_id' => (string) $audit->getKey()]);
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id'     => $audit->getKey(),
            'status' => 'queued',
        ]);
    }

    // Test 9: charge.refunded with matching stripe_payment_intent → status 'refunded'
    public function test_charge_refunded_sets_refunded_status(): void
    {
        $audit = $this->createPendingAudit([
            'status'                => 'queued',
            'paid_at'               => now(),
            'stripe_payment_intent' => 'pi_refund_test',
        ]);
        $payload = $this->chargeRefundedPayload('pi_refund_test');
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id'     => $audit->getKey(),
            'status' => 'refunded',
        ]);
    }

    // Test 10: charge.refunded idempotent (already refunded) → stays 'refunded', no exception
    public function test_charge_refunded_is_idempotent(): void
    {
        $audit = $this->createPendingAudit([
            'status'                => 'refunded',
            'paid_at'               => now(),
            'stripe_payment_intent' => 'pi_already_refunded',
        ]);
        $payload = $this->chargeRefundedPayload('pi_already_refunded');
        $signed = $this->signedWebhookRequest($payload);

        $this->postWebhook($signed['payload'], $signed['signature'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_requests', [
            'id'     => $audit->getKey(),
            'status' => 'refunded',
        ]);
    }
}
