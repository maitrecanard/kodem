<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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
            ],
            'price' => [
                'cents' => $audit->price_cents,
                'label' => number_format($audit->price_cents / 100, 2, ',', ' ').' €',
            ],
            'driver' => config('audit.payment_driver', 'stub'),
        ]);
    }

    public function store(Request $request, Audit $audit, TrackingService $tracking): RedirectResponse
    {
        if ($audit->isPaid()) {
            return redirect()->route('audit.show', $audit->uuid);
        }

        $driver = config('audit.payment_driver', 'stub');

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

            return redirect()
                ->route('audit.show', $audit->uuid)
                ->with('success', 'Paiement simulé — rapport complet débloqué.');
        }

        // Intégration Stripe (via laravel/cashier) à brancher ici.
        abort(501, 'Driver de paiement non supporté : '.$driver);
    }
}
