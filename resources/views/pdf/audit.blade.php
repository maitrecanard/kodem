<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Rapport d'audit — {{ $audit->url }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.4; }
    h1 { color: #1e3a8a; margin: 0 0 6px; font-size: 20px; }
    h2 { color: #1e3a8a; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; margin-top: 22px; font-size: 14px; }
    .meta { color: #64748b; font-size: 10px; margin-bottom: 16px; }
    .scores { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .scores td { width: 33%; text-align: center; padding: 12px; border: 1px solid #e2e8f0; }
    .scores .big { font-size: 26px; font-weight: bold; color: #1e3a8a; }
    .scores .global td { background: #0f172a; color: #fff; border-color: #0f172a; }
    .scores .global .big { color: #fff; }
    table.checks { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.checks th, table.checks td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    table.checks th { background: #f1f5f9; }
    .pass { color: #047857; font-weight: bold; }
    .warn { color: #b45309; font-weight: bold; }
    .fail { color: #be123c; font-weight: bold; }
    .footer { margin-top: 30px; color: #64748b; font-size: 9px; text-align: center; }
    .kv { margin: 4px 0; }
    .kv strong { display: inline-block; min-width: 120px; }
    .action { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #4f46e5; padding: 10px 12px; margin: 10px 0; }
    .action .title { font-weight: bold; font-size: 12px; }
    .action .gain { color: #047857; font-weight: bold; }
    .action .snippet { background: #0f172a; color: #f8fafc; padding: 8px; font-family: DejaVu Sans Mono, monospace; font-size: 9px; white-space: pre-wrap; word-break: break-all; margin-top: 6px; }
    .tag { display: inline-block; border-radius: 10px; padding: 1px 8px; font-size: 9px; font-weight: bold; margin-right: 4px; }
    .tag.fail { background: #fee2e2; color: #991b1b; }
    .tag.warn { background: #fef3c7; color: #92400e; }
</style>
</head>
<body>

<h1>Rapport d'audit Kodem</h1>
<div class="meta">
    <div><strong>URL :</strong> {{ $audit->url }}</div>
    <div><strong>Référence :</strong> {{ $audit->uuid }}</div>
    <div><strong>Généré le :</strong> {{ $generated_at->format('d/m/Y H:i') }}</div>
</div>

<table class="scores">
    <tr>
        <td>
            <div>Score SEO</div>
            <div class="big">{{ $audit->score_seo ?? '—' }}/100</div>
        </td>
        <td>
            <div>Score sécurité</div>
            <div class="big">{{ $audit->score_security ?? '—' }}/100</div>
        </td>
        <td class="global-cell">
            <div>Score global</div>
            <div class="big">{{ $audit->score_total ?? '—' }}/100</div>
        </td>
    </tr>
</table>

@if ($audit->status === 'failed')
    <h2>Erreur d'audit</h2>
    <p>{{ $audit->error }}</p>
@else
    @php
        $actionPlan = $audit->results['action_plan'] ?? null;
    @endphp
    @if ($actionPlan && count($actionPlan['items'] ?? []))
        <h2>Plan d'action vers 100/100</h2>
        <p style="margin:4px 0 10px;">
            Gain potentiel global&nbsp;: <strong class="gain">+{{ $actionPlan['potential_gain_total'] }} pts</strong>
            (SEO +{{ $actionPlan['potential_gain_seo'] }} · Sécurité +{{ $actionPlan['potential_gain_security'] }})
        </p>
        @foreach ($actionPlan['items'] as $i => $item)
            <div class="action">
                <div class="title">{{ $i + 1 }}. {{ $item['label'] }}</div>
                <div style="margin:4px 0;">
                    <span class="tag {{ $item['status'] }}">{{ $item['status'] === 'fail' ? 'à corriger' : 'à améliorer' }}</span>
                    <span class="gain">+{{ $item['potential_gain'] }} pts</span>
                    &nbsp;·&nbsp; {{ strtoupper($item['category']) }}
                </div>
                <div><em>Constat :</em> {{ $item['detail'] }}</div>
                @if (! empty($item['recommendation']['fix']))
                    <div style="margin-top:4px;">{{ $item['recommendation']['fix'] }}</div>
                @endif
                @if (! empty($item['recommendation']['snippet']))
                    <div class="snippet">{{ $item['recommendation']['snippet'] }}</div>
                @endif
                @if (! empty($item['recommendation']['reference']))
                    <div style="font-size:9px; color:#64748b; margin-top:4px;">{{ $item['recommendation']['reference'] }}</div>
                @endif
            </div>
        @endforeach
    @elseif ($actionPlan)
        <h2>Plan d'action</h2>
        <p>🎉 Tous les contrôles sont en pass — score 100/100 atteint.</p>
    @endif

    @php $seo = $audit->results['seo']['checks'] ?? []; @endphp
    @if ($seo)
        <h2>Contrôles SEO</h2>
        <table class="checks">
            <thead><tr><th>Contrôle</th><th>Statut</th><th>Détail</th></tr></thead>
            <tbody>
                @foreach ($seo as $c)
                    <tr>
                        <td>{{ $c['label'] }}</td>
                        <td class="{{ $c['status'] ?? 'fail' }}">
                            {{ strtoupper($c['status'] ?? 'fail') }}
                        </td>
                        <td>{{ $c['detail'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @php $sec = $audit->results['security']['checks'] ?? []; @endphp
    @if ($sec)
        <h2>Contrôles de sécurité</h2>
        <table class="checks">
            <thead><tr><th>Contrôle</th><th>Statut</th><th>Détail</th></tr></thead>
            <tbody>
                @foreach ($sec as $c)
                    <tr>
                        <td>{{ $c['label'] }}</td>
                        <td class="{{ $c['status'] ?? 'fail' }}">
                            {{ strtoupper($c['status'] ?? 'fail') }}
                        </td>
                        <td>{{ $c['detail'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($audit->cwv_results)
        <h2>Core Web Vitals (Google PageSpeed Insights)</h2>
        <div class="kv"><strong>Performance :</strong> {{ $audit->cwv_results['performance_score'] ?? '—' }}/100</div>
        <div class="kv"><strong>LCP :</strong> {{ $audit->cwv_results['lcp'] ?? '—' }}</div>
        <div class="kv"><strong>CLS :</strong> {{ $audit->cwv_results['cls'] ?? '—' }}</div>
        <div class="kv"><strong>INP :</strong> {{ $audit->cwv_results['inp'] ?? '—' }}</div>
        <div class="kv"><strong>TBT :</strong> {{ $audit->cwv_results['tbt'] ?? '—' }}</div>
    @endif
@endif

<div class="footer">
    Kodem — Développement web · Hébergement · Audits SEO et sécurité automatisés · kodem.fr
</div>

</body>
</html>
