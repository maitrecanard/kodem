<?php

namespace App\Http\Controllers;

use App\Models\MonitoringSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function create(): Response
    {
        $price = (int) config('audit.monitoring_price_cents', 4900);
        $period = (int) config('audit.monitoring_period_days', 30);

        return Inertia::render('Public/MonitoringSubscribe', [
            'meta' => [
                'title' => 'Monitoring SEO et sécurité mensuel — Kodem',
                'description' => 'Abonnement de monitoring automatisé : audits SEO et sécurité hebdomadaires, alertes email en cas de régression.',
                'keywords' => 'monitoring SEO, monitoring sécurité, surveillance site web',
            ],
            'price' => [
                'cents' => $price,
                'label' => number_format($price / 100, 2, ',', ' ').' €',
            ],
            'period_days' => $period,
            'driver' => config('audit.payment_driver', 'stub'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/)?[^\s]+\.[^\s]+$/i'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            'confirm' => ['required', 'accepted'],
        ]);

        $driver = config('audit.payment_driver', 'stub');
        if ($driver !== 'stub') {
            abort(501, 'Driver de paiement non supporté : '.$driver);
        }

        $period = (int) config('audit.monitoring_period_days', 30);

        $sub = MonitoringSubscription::create([
            'url' => $validated['url'],
            'email' => $validated['email'],
            'price_cents' => (int) config('audit.monitoring_price_cents', 4900),
            'status' => 'active',
            'active_until' => now()->addDays($period),
            'payment_reference' => 'STUB-MON-'.strtoupper(Str::random(10)),
        ]);

        return redirect()
            ->route('monitoring.show', $sub->token)
            ->with('success', 'Abonnement activé — vous recevrez un rapport hebdomadaire par email.');
    }

    public function show(MonitoringSubscription $subscription): Response
    {
        return Inertia::render('Public/MonitoringDashboard', [
            'meta' => [
                'title' => 'Tableau de bord monitoring — Kodem',
                'description' => 'Tableau de bord de votre abonnement monitoring Kodem.',
                'keywords' => 'monitoring',
            ],
            'subscription' => [
                'token' => $subscription->token,
                'url' => $subscription->url,
                'email' => $subscription->email,
                'status' => $subscription->status,
                'active' => $subscription->isActive(),
                'active_until' => $subscription->active_until?->toIso8601String(),
                'last_run_at' => $subscription->last_run_at?->toIso8601String(),
                'last_score_total' => $subscription->last_score_total,
                'last_audit_uuid' => $subscription->last_audit_uuid,
            ],
        ]);
    }

    public function cancel(MonitoringSubscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'cancelled']);
        return back()->with('success', 'Abonnement annulé. Il reste actif jusqu\'à la fin de la période en cours.');
    }
}
