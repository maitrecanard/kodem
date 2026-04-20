<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\PageSpeedClient;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuditCwvController extends Controller
{
    public function show(Request $request, Audit $audit): Response|RedirectResponse
    {
        $this->requirePaidAudit($audit);

        $isAdmin = (bool) optional($request->user())->is_admin;
        if (! $audit->isCwvPaid() && ! $isAdmin) {
            return redirect()->route('audit.cwv.pay', $audit->uuid);
        }

        return Inertia::render('Public/AuditCwv', [
            'meta' => [
                'title' => 'Core Web Vitals — audit Kodem',
                'description' => 'Rapport de performance Core Web Vitals (Google PageSpeed Insights).',
                'keywords' => 'core web vitals, performance, LCP, CLS, INP',
            ],
            'audit' => [
                'uuid' => $audit->uuid,
                'url' => $audit->url,
                'cwv_results' => $audit->cwv_results,
            ],
        ]);
    }

    public function pay(Audit $audit): Response|RedirectResponse
    {
        $this->requirePaidAudit($audit);

        if ($audit->isCwvPaid()) {
            return redirect()->route('audit.cwv', $audit->uuid);
        }

        return Inertia::render('Public/AuditCwvCheckout', [
            'meta' => [
                'title' => 'Add-on Core Web Vitals — Kodem',
                'description' => 'Analyse de performance via Google PageSpeed Insights.',
                'keywords' => 'core web vitals, PageSpeed, performance',
            ],
            'audit' => [
                'uuid' => $audit->uuid,
                'url' => $audit->url,
                'score_total' => $audit->score_total,
            ],
            'price' => [
                'cents' => $audit->cwv_price_cents,
                'label' => number_format($audit->cwv_price_cents / 100, 2, ',', ' ').' €',
            ],
            'driver' => config('audit.payment_driver', 'stub'),
        ]);
    }

    public function confirmPayment(Request $request, Audit $audit, PageSpeedClient $client, TrackingService $tracking): RedirectResponse
    {
        $this->requirePaidAudit($audit);

        if ($audit->isCwvPaid()) {
            return redirect()->route('audit.cwv', $audit->uuid);
        }

        $driver = config('audit.payment_driver', 'stub');
        if ($driver !== 'stub') {
            abort(501, 'Driver de paiement non supporté : '.$driver);
        }

        $request->validate(['confirm' => ['required', 'accepted']]);

        $result = $client->run($audit->url);

        $audit->update([
            'cwv_paid_at' => now(),
            'cwv_results' => $result,
            'payment_reference' => trim(($audit->payment_reference ?? '').' CWV-'.strtoupper(Str::random(8))),
        ]);

        $tracking->record('audit.cwv.paid', 'audit_'.substr($audit->uuid, 0, 8), [
            'audit_uuid' => $audit->uuid,
            'price_cents' => $audit->cwv_price_cents,
            'psi_status' => $result['status'] ?? null,
            'performance_score' => $result['performance_score'] ?? null,
        ], $request);

        return redirect()
            ->route('audit.cwv', $audit->uuid)
            ->with('success', 'Add-on Core Web Vitals débloqué.');
    }

    protected function requirePaidAudit(Audit $audit): void
    {
        if (! $audit->isPaid()) {
            abort(redirect()->route('audit.pay', $audit->uuid)
                ->with('error', 'Payez d\'abord le rapport complet pour débloquer les Core Web Vitals.'));
        }
    }
}
