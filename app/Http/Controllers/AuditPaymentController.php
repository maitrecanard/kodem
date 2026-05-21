<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\DiscordNotifier;
use App\Services\StripeCheckoutService;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditPaymentController extends Controller
{
    public function create(Audit $audit): Response|RedirectResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
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
                'email' => $audit->email,
            ],
            'price' => [
                'cents' => $audit->price_cents,
                'label' => number_format($audit->price_cents / 100, 2, ',', ' ').' €',
            ],
            'driver' => config('audit.payment_driver', 'stub'),
        ]);
    }

    public function store(Request $request, Audit $audit, TrackingService $tracking, DiscordNotifier $discord): SymfonyResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
        }

        $driver = config('audit.payment_driver', 'stub');

        if ($driver === 'stripe') {
            // Validation : on n'autorise la redirection vers Stripe que si le
            // client a saisi une adresse email — sinon on la lui redemande.
            $validated = $request->validate([
                'email' => ['required', 'string', 'email:rfc', 'max:180'],
                'confirm' => ['required', 'accepted'],
            ]);

            // Jeton privé transmis dans l'URL de confirmation.
            $token = Str::random(64);

            $audit->update([
                'email' => $validated['email'],
                'payment_token' => $token,
            ]);

            $session = app(StripeCheckoutService::class)
                ->createAuditSession($audit, $validated['email'], $token);

            $audit->update(['stripe_session_id' => $session->id]);

            $tracking->record('audit.checkout.started', 'audit_'.substr($audit->uuid, 0, 8), [
                'audit_uuid' => $audit->uuid,
                'price_cents' => $audit->price_cents,
                'driver' => 'stripe',
            ], $request);

            // Redirection externe vers la page de paiement hébergée par Stripe.
            return Inertia::location($session->url);
        }

        if ($driver === 'stub') {
            $request->validate([
                'confirm' => ['required', 'accepted'],
            ]);

            $audit->update([
                'paid_at' => now(),
                'payment_reference' => 'STUB-'.strtoupper(Str::random(12)),
            ]);

            $tracking->record('audit.paid', 'audit_'.substr($audit->uuid, 0, 8), [
                'audit_uuid' => $audit->uuid,
                'price_cents' => $audit->price_cents,
                'driver' => 'stub',
            ], $request);

            $discord->notifyAuditEvent(DiscordNotifier::EVENT_PAID, $audit->fresh(), [
                'Montant' => number_format($audit->price_cents / 100, 2, ',', ' ').' €',
                'Driver' => 'stub',
            ]);

            return redirect()
                ->route('audit.show', $audit->uuid)
                ->with('success', 'Paiement simulé — rapport complet débloqué.');
        }

        abort(501, 'Driver de paiement non supporté : '.$driver);
    }

    /**
     * Retour depuis Stripe Checkout : on valide le jeton privé puis on
     * re-vérifie le statut de la session côté API Stripe avant de marquer
     * l'audit comme payé. Le success_url seul ne fait jamais foi.
     */
    public function confirm(Request $request, Audit $audit, TrackingService $tracking, DiscordNotifier $discord): RedirectResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
        }

        $token = (string) $request->query('token', '');

        if ($audit->payment_token === null || ! hash_equals($audit->payment_token, $token)) {
            abort(403, 'Lien de confirmation de paiement invalide.');
        }

        if ($audit->stripe_session_id === null) {
            return $this->backToCheckout($audit);
        }

        $session = app(StripeCheckoutService::class)->retrieveSession($audit->stripe_session_id);

        if (($session->payment_status ?? null) !== 'paid') {
            return $this->backToCheckout($audit);
        }

        $audit->update([
            'paid_at' => now(),
            'payment_reference' => (string) ($session->payment_intent ?? $session->id),
            'payment_token' => null,
        ]);

        $tracking->record('audit.paid', 'audit_'.substr($audit->uuid, 0, 8), [
            'audit_uuid' => $audit->uuid,
            'price_cents' => $audit->price_cents,
            'driver' => 'stripe',
        ], $request);

        $discord->notifyAuditEvent(DiscordNotifier::EVENT_PAID, $audit->fresh(), [
            'Montant' => number_format($audit->price_cents / 100, 2, ',', ' ').' €',
            'Driver' => 'stripe',
        ]);

        return redirect()
            ->route('audit.show', $audit->uuid)
            ->with('success', 'Paiement confirmé — rapport complet débloqué.');
    }

    private function backToCheckout(Audit $audit): RedirectResponse
    {
        return redirect()
            ->route('audit.pay', $audit->uuid)
            ->with('error', 'Le paiement n\'a pas encore été confirmé. Réessayez si vous avez bien réglé.');
    }
}
