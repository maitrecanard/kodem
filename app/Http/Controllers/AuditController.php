<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\AuditRunner;
use App\Services\PrestationCatalog;
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
                'title' => 'Audit SEO et audit de sécurité en ligne, gratuit — Kodem',
                'description' => 'Lancez un audit SEO et un audit de sécurité automatisés gratuitement en 30 secondes. Score sur 100, rapport détaillé, recommandations.',
                'keywords' => 'audit SEO en ligne, audit de sécurité gratuit, analyse SEO automatique',
            ],
            'paidPrestations' => PrestationCatalog::all(),
        ]);
    }

    public function store(Request $request, AuditRunner $runner): RedirectResponse
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
            'ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
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

        return redirect()->route('audit.show', $audit->uuid);
    }

    public function show(Audit $audit): Response
    {
        return Inertia::render('Public/AuditResult', [
            'meta' => [
                'title' => 'Résultat de l\'audit — Kodem',
                'description' => 'Rapport d\'audit SEO et de sécurité automatisé par Kodem.',
                'keywords' => 'audit SEO, audit de sécurité, rapport',
            ],
            'audit' => [
                'uuid' => $audit->uuid,
                'url' => $audit->url,
                'status' => $audit->status,
                'score_seo' => $audit->score_seo,
                'score_security' => $audit->score_security,
                'score_total' => $audit->score_total,
                'results' => $audit->results,
                'error' => $audit->error,
                'created_at' => $audit->created_at?->toIso8601String(),
            ],
            'paidPrestations' => PrestationCatalog::all(),
        ]);
    }
}
