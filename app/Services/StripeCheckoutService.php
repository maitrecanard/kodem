<?php

namespace App\Services;

use App\Models\Audit;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * Encapsule les appels à l'API Stripe Checkout pour le paiement d'un audit.
 *
 * Mode "payment" (paiement unique, marchand unique — pas de Stripe Connect).
 * Le service est volontairement mince et injectable afin de pouvoir être
 * remplacé par un faux en test (voir tests/Feature/AuditPaymentTest.php).
 */
class StripeCheckoutService
{
    private StripeClient $client;

    public function __construct(?StripeClient $client = null)
    {
        $this->client = $client ?? new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * Crée une session Stripe Checkout pour débloquer le rapport complet.
     *
     * @param  string  $token  Jeton privé transmis dans l'URL de confirmation.
     */
    public function createAuditSession(Audit $audit, string $email, string $token): Session
    {
        return $this->client->checkout->sessions->create(
            $this->buildAuditSessionParams($audit, $email, $token)
        );
    }

    /**
     * Construit les paramètres de la session Stripe Checkout.
     *
     * Extrait dans une méthode dédiée pour pouvoir être testé unitairement
     * sans appeler l'API Stripe.
     *
     * @param  string  $token  Jeton privé transmis dans l'URL de confirmation.
     * @return array<string,mixed>
     */
    public function buildAuditSessionParams(Audit $audit, string $email, string $token): array
    {
        return [
            'mode' => 'payment',
            'customer_email' => $email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $audit->price_cents,
                    'product_data' => [
                        'name' => 'Rapport d\'audit complet — Kodem',
                        'description' => 'Audit SEO et sécurité pour '.$audit->url,
                    ],
                ],
            ]],
            'success_url' => route('audit.pay.confirm', ['audit' => $audit->uuid, 'token' => $token]),
            'cancel_url' => route('audit.pay', ['audit' => $audit->uuid]),
            'metadata' => [
                'audit_uuid' => $audit->uuid,
                'token' => $token,
            ],
            'expires_at' => now()->addMinutes(30)->timestamp,
        ];
    }

    /**
     * Récupère une session pour vérifier son statut de paiement côté serveur.
     */
    public function retrieveSession(string $sessionId): Session
    {
        return $this->client->checkout->sessions->retrieve($sessionId);
    }
}
