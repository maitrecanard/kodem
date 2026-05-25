<?php

declare(strict_types=1);

namespace App\Modules\Audits\Services;

use App\Modules\Audits\Models\AuditRequest;
use Stripe\StripeClient;
use Stripe\Checkout\Session;

class StripeCheckoutService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createSessionForAuditRequest(AuditRequest $auditRequest): Session
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],

            'line_items' => [[
                'price_data' => [
                    'currency' => config('audits.premium.currency'),
                    'product_data' => [
                        'name' => config('audits.premium.product_name'),
                        'description' => "Audit SEO + Sécurité pour {$auditRequest->domain} — livraison sous 24-48h",
                    ],
                    'unit_amount' => config('audits.premium.price_cents'),
                ],
                'quantity' => 1,
            ]],

            'customer_email' => $auditRequest->email,

            'success_url' => route('audits.merci') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('audits.annule'),

            'metadata' => [
                'audit_request_id' => (string) $auditRequest->id,
                'domain' => $auditRequest->domain,
            ],

            'expires_at' => now()->addMinutes(
                config('audits.premium.session_expires_minutes')
            )->timestamp,

            'locale' => 'fr',

            'invoice_creation' => [
                'enabled' => true,
            ],

            'billing_address_collection' => 'required',
        ]);

        $auditRequest->update([
            'stripe_session_id' => $session->id,
        ]);

        return $session;
    }
}
