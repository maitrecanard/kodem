<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\DiscordNotifier;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuditPaymentController extends Controller
{
    public function create(Audit $audit): Response|HttpResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
        }

        $driver = config('audit.payment_driver', 'stub');

        if ($driver === 'stripe') {
            return $this->redirectToStripeCheckout($audit);
        }

        return Inertia::render('Public/AuditCheckout', [
            'meta' => [
                'title' => 'Débloquer le rapport complet — Kodem',
                'description' => 'Paiement du rapport d\'audit SEO et sécurité complet.',
                'keywords' => 'audit payant, rapport SEO, rapport sécurité',
            ],
            'audit' => [
                'uuid' => $audit->uuid,
                'url' => $audit->url,
                'score_total' => $audit->score_total,
            ],
            'price' => [
                'cents' => $audit->price_cents,
                'label' => number_format($audit->price_cents / 100, 2, ',', ' ').' €',
            ],
            'driver' => $driver,
        ]);
    }

    public function store(Request $request, Audit $audit, TrackingService $tracking, DiscordNotifier $discord): RedirectResponse|HttpResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
        }

        $driver = config('audit.payment_driver', 'stub');

        if ($driver === 'stripe') {
            return $this->redirectToStripeCheckout($audit);
        }

        // Driver "stub" : simulation de paiement (environnement de dev).
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        $this->markPaid($audit, 'STUB-'.strtoupper(Str::random(12)), 'stub', $tracking, $discord, $request);

        return redirect()
            ->route('audit.show', $audit->uuid)
            ->with('success', 'Paiement simulé — rapport complet débloqué.');
    }

    /**
     * Retour depuis Stripe Checkout : on vérifie la session et on débloque le rapport.
     */
    public function success(Request $request, Audit $audit, TrackingService $tracking, DiscordNotifier $discord): RedirectResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
        }

        $sessionId = $request->query('session_id');

        if (! is_string($sessionId) || $sessionId === '') {
            return redirect()
                ->route('audit.show', $audit->uuid)
                ->with('error', 'Paiement non confirmé. Aucune session Stripe fournie.');
        }

        $session = $this->stripe()->checkout->sessions->retrieve($sessionId);

        $belongsToAudit = ($session->metadata->audit_uuid ?? null) === $audit->uuid;

        if (! $belongsToAudit || $session->payment_status !== 'paid') {
            return redirect()
                ->route('audit.show', $audit->uuid)
                ->with('error', 'Paiement non confirmé par Stripe.');
        }

        $reference = is_string($session->payment_intent)
            ? $session->payment_intent
            : (string) $session->id;

        $this->markPaid($audit, $reference, 'stripe', $tracking, $discord, $request);

        return redirect()
            ->route('audit.show', $audit->uuid)
            ->with('success', 'Paiement confirmé — rapport complet débloqué.');
    }

    private function redirectToStripeCheckout(Audit $audit): HttpResponse
    {
        $session = $this->stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [$this->lineItem($audit)],
            'locale' => 'fr',
            'customer_email' => $audit->email,
            'metadata' => [
                'audit_uuid' => $audit->uuid,
            ],
            'success_url' => route('audit.pay.success', $audit->uuid).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('audit.show', $audit->uuid),
        ]);

        // Inertia::location renvoie l'en-tête X-Inertia-Location (visite XHR)
        // ou une redirection 302 classique pour une requête non-Inertia.
        return Inertia::location($session->url);
    }

    /**
     * Ligne de commande Stripe : prix dynamique aligné sur le montant affiché
     * à l'audit (price_cents), pour garantir la cohérence page / facturation.
     *
     * @return array<string, mixed>
     */
    private function lineItem(Audit $audit): array
    {
        return [
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $audit->price_cents,
                'product_data' => [
                    'name' => 'Rapport d\'audit complet — '.$audit->url,
                ],
            ],
        ];
    }

    private function markPaid(
        Audit $audit,
        string $reference,
        string $driver,
        TrackingService $tracking,
        DiscordNotifier $discord,
        Request $request
    ): void {
        $audit->update([
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);

        $tracking->record('audit.paid', 'audit_'.substr($audit->uuid, 0, 8), [
            'audit_uuid' => $audit->uuid,
            'price_cents' => $audit->price_cents,
            'driver' => $driver,
        ], $request);

        $discord->notifyAuditEvent(DiscordNotifier::EVENT_PAID, $audit->fresh(), [
            'Montant' => number_format($audit->price_cents / 100, 2, ',', ' ').' €',
            'Driver' => $driver,
        ]);
    }

    private function stripe(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }
}
