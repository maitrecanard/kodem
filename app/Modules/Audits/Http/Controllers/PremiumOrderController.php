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
    /** Page de vente du premium. */
    public function show()
    {
        return Inertia::render('Audits/Premium', [
            'price_cents' => config('audits.premium.price_cents'),
            'currency'    => config('audits.premium.currency'),
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
        return Inertia::render('Audits/Merci');
    }

    public function annule()
    {
        return Inertia::render('Audits/Annule');
    }
}
