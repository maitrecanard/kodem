<!doctype html>
<html>
<head><meta charset="utf-8"><title>Votre audit Kodem</title></head>
<body style="font-family: 'Space Grotesk', -apple-system, Segoe UI, Helvetica, sans-serif; color:#0B1B4D; background:#FBFCFE; padding:24px;">
    <div style="max-width:560px; margin:auto; background:#fff; border:1px solid #E8ECFF; border-radius:12px; overflow:hidden;">
        <div style="background:#0B1B4D; color:#fff; padding:20px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                <td style="padding-right:10px; vertical-align:middle;">
                    <img src="{{ url('/email-logo-kodem.png') }}" width="32" height="32" alt=""
                         style="display:block; width:32px; height:32px; border:0;">
                </td>
                <td style="vertical-align:middle; font-size:12px; letter-spacing:2px; text-transform:uppercase; opacity:.8; color:#fff;">[kodem]</td>
            </tr></table>
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
                   style="display:inline-block; background:#2348E0; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600;">
                    Revoir mon rapport
                </a>
                &nbsp;
                <a href="{{ url('/contact') }}"
                   style="display:inline-block; background:#0B1B4D; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:600;">
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
