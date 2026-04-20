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
