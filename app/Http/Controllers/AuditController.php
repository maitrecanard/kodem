<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\AuditRunner;
use App\Services\DiscordNotifier;
use App\Services\PrestationCatalog;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/Audit', [
            'meta' => [
                'title' => 'Audit SEO et audit de sécurité en ligne — Kodem',
                'description' => 'Lancez un audit SEO et un audit de sécurité automatisés en 30 secondes. Aperçu gratuit, rapport détaillé à 29 €.',
                'keywords' => 'audit SEO en ligne, audit de sécurité payant, rapport audit',
            ],
            'price' => $this->priceLabel(),
            'paidPrestations' => PrestationCatalog::all(),
        ]);
    }

    public function store(Request $request, AuditRunner $runner, TrackingService $tracking, DiscordNotifier $discord): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/)?[^\s]+\.[^\s]+$/i'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:180'],
            'type' => ['nullable', 'in:full,seo,security'],
        ]);

        $audit = Audit::create([
            'url' => $validated['url'],
            'email' => $validated['email'] ?? null,
            'type' => $validated['type'] ?? 'full',
            'status' => 'running',
            'price_cents' => (int) config('audit.price_cents', 2900),
            'ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
        ]);

        $tracking->record('audit.submitted', 'audit_'.substr($audit->uuid, 0, 8), [
            'audit_uuid' => $audit->uuid,
            'type' => $audit->type,
            'has_email' => ! empty($validated['email']),
        ], $request);

        $discord->notifyAuditEvent(DiscordNotifier::EVENT_SUBMITTED, $audit, [
            'Type' => $audit->type,
        ]);

        $outcome = $runner->run($validated['url']);

        $audit->update([
            'status' => $outcome['status'],
            'score_seo' => $outcome['score_seo'],
            'score_security' => $outcome['score_security'],
            'score_total' => $outcome['score_total'],
            'results' => $outcome['results'],
            'error' => $outcome['error'],
        ]);

        $tracking->record(
            $outcome['status'] === 'completed' ? 'audit.completed' : 'audit.failed',
            'audit_'.substr($audit->uuid, 0, 8),
            [
                'audit_uuid' => $audit->uuid,
                'score_total' => $outcome['score_total'],
                'score_seo' => $outcome['score_seo'],
                'score_security' => $outcome['score_security'],
            ],
            $request
        );

        $discord->notifyAuditEvent(
            $outcome['status'] === 'completed' ? DiscordNotifier::EVENT_COMPLETED : DiscordNotifier::EVENT_FAILED,
            $audit->fresh(),
            $outcome['status'] === 'failed' ? ['Erreur' => (string) $outcome['error']] : []
        );

        return redirect()->route('audit.show', $audit->uuid);
    }

    public function show(Request $request, Audit $audit): Response
    {
        $isAdmin = (bool) optional($request->user())->is_admin;
        $paid = $audit->isPaid() || $isAdmin;

        return Inertia::render('Public/AuditResult', [
            'meta' => [
                'title' => 'Résultat de l\'audit — Kodem',
                'description' => 'Rapport d\'audit SEO et de sécurité Kodem.',
                'keywords' => 'audit SEO, audit de sécurité, rapport',
            ],
            'audit' => $this->serializeAudit($audit, $paid),
            'paid' => $paid,
            'price' => $this->priceLabel($audit->price_cents),
            'paidPrestations' => PrestationCatalog::all(),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function serializeAudit(Audit $audit, bool $paid): array
    {
        $base = [
            'uuid' => $audit->uuid,
            'url' => $audit->url,
            'status' => $audit->status,
            'score_total' => $audit->score_total,
            'paid' => $paid,
            'paid_at' => $audit->paid_at?->toIso8601String(),
            'error' => $audit->error,
            'created_at' => $audit->created_at?->toIso8601String(),
        ];

        if ($paid) {
            return array_merge($base, [
                'score_seo' => $audit->score_seo,
                'score_security' => $audit->score_security,
                'results' => $audit->results,
                'pdf_paid' => $audit->isPdfPaid(),
                'cwv_paid' => $audit->isCwvPaid(),
                'pdf_price_label' => number_format($audit->pdf_price_cents / 100, 2, ',', ' ').' €',
                'cwv_price_label' => number_format($audit->cwv_price_cents / 100, 2, ',', ' ').' €',
            ]);
        }

        // Aperçu gratuit : score global uniquement + comptage par catégorie.
        return array_merge($base, [
            'score_seo' => null,
            'score_security' => null,
            'teaser' => $this->buildTeaser($audit),
            'results' => null,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function buildTeaser(Audit $audit): array
    {
        $results = $audit->results ?? [];
        $counts = fn (array $checks) => [
            'pass' => count(array_filter($checks, fn ($c) => ($c['status'] ?? null) === 'pass')),
            'warn' => count(array_filter($checks, fn ($c) => ($c['status'] ?? null) === 'warn')),
            'fail' => count(array_filter($checks, fn ($c) => ($c['status'] ?? null) === 'fail')),
            'total' => count($checks),
        ];

        return [
            'seo_counts' => $counts($results['seo']['checks'] ?? []),
            'security_counts' => $counts($results['security']['checks'] ?? []),
            'sample_check' => $results['security']['checks'][0] ?? null,
        ];
    }

    /**
     * @return array{cents:int, label:string}
     */
    protected function priceLabel(?int $cents = null): array
    {
        $cents ??= (int) config('audit.price_cents', 2900);
        return [
            'cents' => $cents,
            'label' => number_format($cents / 100, 2, ',', ' ').' €',
        ];
    }
}
