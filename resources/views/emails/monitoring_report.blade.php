<!doctype html>
<html>
<head><meta charset="utf-8"><title>Rapport monitoring</title></head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, sans-serif; color:#1e293b; background:#f8fafc; padding:24px;">
    <div style="max-width:560px; margin:auto; background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
        <div style="background: {{ $alert ? '#be123c' : '#1e3a8a' }}; color:#fff; padding:20px;">
            <div style="font-size:12px; letter-spacing:2px; text-transform:uppercase; opacity:.8;">Kodem monitoring</div>
            <div style="font-size:20px; font-weight:bold; margin-top:4px;">
                {{ $alert ? 'Alerte : régression détectée' : 'Rapport périodique' }}
            </div>
        </div>
        <div style="padding:20px;">
            <p>Bonjour,</p>
            <p>Nous venons de lancer un audit automatique de <strong>{{ $subscription->url }}</strong>.</p>

            @if ($audit->status === 'failed')
                <p style="color:#be123c;"><strong>Audit impossible :</strong> {{ $audit->error }}</p>
            @else
                <p>
                    Score global : <strong>{{ $audit->score_total }}/100</strong>
                    @if ($previous !== null)
                        (précédent : {{ $previous }}/100 — {{ $audit->score_total > $previous ? '+' : '' }}{{ $audit->score_total - $previous }})
                    @endif
                </p>
                <ul>
                    <li>SEO : {{ $audit->score_seo }}/100</li>
                    <li>Sécurité : {{ $audit->score_security }}/100</li>
                </ul>
                @if ($alert)
                    <p style="background:#fef2f2; border-left:4px solid #be123c; padding:12px;">
                        Le score a chuté de plus de {{ config('audit.monitoring_alert_threshold', 10) }} points depuis le dernier contrôle.
                        Nous vous recommandons d'inspecter le rapport complet dès que possible.
                    </p>
                @endif
            @endif

            <p style="margin-top:20px;">
                <a href="{{ url('/audit/'.$audit->uuid) }}"
                   style="display:inline-block; background:#4f46e5; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600;">
                    Voir le rapport complet
                </a>
            </p>
            <p style="color:#64748b; font-size:12px; margin-top:30px;">
                Vous recevez ce message car vous êtes abonné au monitoring Kodem.<br>
                Gérer l'abonnement : <a href="{{ url('/monitoring/'.$subscription->token) }}">{{ url('/monitoring/'.$subscription->token) }}</a>
            </p>
        </div>
    </div>
</body>
</html>
