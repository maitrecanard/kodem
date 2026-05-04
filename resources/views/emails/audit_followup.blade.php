<!doctype html>
<html>
<head><meta charset="utf-8"><title>Votre audit Kodem</title></head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, sans-serif; color:#1e293b; background:#f8fafc; padding:24px;">
    <div style="max-width:560px; margin:auto; background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
        <div style="background:#1e3a8a; color:#fff; padding:20px;">
            <div style="font-size:12px; letter-spacing:2px; text-transform:uppercase; opacity:.8;">Kodem</div>
            <div style="font-size:20px; font-weight:bold; margin-top:4px;">
                Votre site mérite mieux que {{ $audit->score_total }}/100
            </div>
        </div>
        <div style="padding:20px;">
            <p>Bonjour,</p>
            <p>
                Il y a une semaine, vous avez audité <strong>{{ $audit->url }}</strong> avec Kodem.
                Le score global obtenu est de <strong>{{ $audit->score_total }}/100</strong>
                (SEO {{ $audit->score_seo }}/100, sécurité {{ $audit->score_security }}/100).
            </p>
            <p>
                C'est en-dessous du seuil de 75 % que nous considérons comme satisfaisant.
                Nous pouvons vous aider à corriger les points bloquants rapidement.
            </p>

            @if (! empty($recos))
                <p style="margin-top:18px;"><strong>Points prioritaires détectés :</strong></p>
                <ul style="padding-left:18px;">
                    @foreach ($recos as $reco)
                        <li style="margin-bottom:8px;">{{ $reco }}</li>
                    @endforeach
                </ul>
            @endif

            <p style="margin-top:24px;">
                <a href="{{ url('/audit/'.$audit->uuid) }}"
                   style="display:inline-block; background:#4f46e5; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600;">
                    Revoir mon rapport
                </a>
                &nbsp;
                <a href="{{ url('/contact') }}"
                   style="display:inline-block; background:#0f172a; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600;">
                    Demander un devis
                </a>
            </p>

            <p style="color:#64748b; font-size:12px; margin-top:30px;">
                Vous recevez ce message car vous avez demandé un audit gratuit sur Kodem.
                <br>
                <a href="{{ $unsubscribeUrl }}">Ne plus recevoir ce type de relance pour cet audit</a>
            </p>
        </div>
    </div>
</body>
</html>
