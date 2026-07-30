<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audits\Http\Requests\CreatePremiumOrderRequest;
use App\Modules\Audits\Models\AuditRequest;
use App\Modules\Audits\Services\StripeCheckoutService;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PremiumOrderController extends Controller
{
    /** Page de vente du rapport complet. */
    public function show()
    {
        return Inertia::render('Audits/Premium', [
            'meta' => [
                'title' => 'Rapport SEO & sécurité complet — 150 € — Kodem',
                'description' => 'Rapport SEO et sécurité rédigé manuellement pour votre site, livré sous 24 à 48 h. 150 €, réservé aux professionnels.',
            ],
            'price_cents' => config('audits.premium.price_cents'),
            'currency' => config('audits.premium.currency'),
            // Conditions commerciales affichées à côté du prix : le rapport est
            // rédigé à la main, ce n'est pas la sortie automatique de l'outil.
            'delivery_hours' => config('audits.premium.delivery_hours'),
            'manual' => config('audits.premium.manual'),
            'audience' => config('audits.premium.audience'),
            'vat_mention' => config('audits.vat.applicable') ? null : config('audits.vat.legal_mention'),
        ]);
    }

    /** Création de la session Stripe + redirection externe via Inertia::location. */
    public function createCheckoutSession(
        CreatePremiumOrderRequest $request,
        StripeCheckoutService $stripe
    ) {
        // Anti-bot silencieux : honeypot ou form rempli trop vite
        $tooFast = (now()->timestamp - (int) $request->form_loaded_at) < 3;
        if ($request->filled('website') || $tooFast) {
            \Illuminate\Support\Facades\Log::warning('Audits premium: soumission rejetée (anti-bot)', [
                'honeypot' => $request->filled('website'),
                'too_fast' => $tooFast,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'stripe' => 'Une erreur est survenue, veuillez réessayer dans quelques instants.',
            ]);
        }

        $auditRequest = AuditRequest::create([
            'type'         => 'premium',
            'email'        => $request->email,
            'name'         => $request->name,
            'domain'       => $request->domain,
            'options'      => $request->options,
            'status'       => 'pending_payment',
            'amount_cents' => config('audits.premium.price_cents'),
            'currency'     => config('audits.premium.currency'),
            'access_token' => Str::random(64),
            'ip_address'   => $request->ip(),
            'user_agent'   => substr((string) $request->userAgent(), 0, 255),
        ]);

        try {
            $session = $stripe->createSessionForAuditRequest($auditRequest);
        } catch (\Throwable $e) {
            report($e);
            $auditRequest->update(['status' => 'failed']);

            return back()->withErrors([
                'stripe' => 'Impossible de créer la session de paiement, réessayez dans un instant.',
            ]);
        }

        // ⚠️ POINT CRITIQUE : sortie d'Inertia vers une URL externe
        return Inertia::location($session->url);
    }

    public function merci()
    {
        return Inertia::render('Audits/Merci', [
            'meta' => [
                'title' => 'Commande confirmée — Kodem',
                // Page de retour de paiement : sans intérêt pour un moteur, et on
                // évite qu'elle remonte dans les résultats de recherche.
                'description' => 'Confirmation de votre commande de rapport SEO & sécurité.',
                'robots' => 'noindex, nofollow',
            ],
            'delivery_hours' => config('audits.premium.delivery_hours'),
            'contact_email' => config('audits.admin_email'),
        ]);
    }

    public function annule()
    {
        return Inertia::render('Audits/Annule');
    }
}
