<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Nouveau message de contact</title></head>
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
            <div style="font-size:20px; font-weight:bold; margin-top:4px;">Nouveau message depuis le site</div>
        </div>
        <div style="padding:20px;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <td style="padding:6px 0; color:#64748B; width:110px;">Nom</td>
                    <td style="padding:6px 0; font-weight:600;">{{ $contactMessage->name }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748B;">E-mail</td>
                    <td style="padding:6px 0;">
                        <a href="mailto:{{ $contactMessage->email }}" style="color:#2348E0;">{{ $contactMessage->email }}</a>
                    </td>
                </tr>
                @if ($contactMessage->company)
                    <tr>
                        <td style="padding:6px 0; color:#64748B;">Structure</td>
                        <td style="padding:6px 0;">{{ $contactMessage->company }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:6px 0; color:#64748B;">Sujet</td>
                    <td style="padding:6px 0; font-weight:600;">{{ $contactMessage->subject }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748B;">Reçu le</td>
                    <td style="padding:6px 0;">{{ $contactMessage->created_at?->format('d/m/Y à H:i') }}</td>
                </tr>
            </table>

            <div style="margin-top:18px; padding:16px; background:#FBFCFE; border:1px solid #E8ECFF; border-radius:8px; white-space:pre-line; font-size:14px; line-height:1.6;">{{ $contactMessage->message }}</div>

            <p style="margin-top:18px; font-size:13px; color:#64748B;">
                Répondez directement à cet e-mail : la réponse partira vers
                {{ $contactMessage->email }}.
            </p>
        </div>
        <div style="padding:14px 20px; border-top:1px solid #E8ECFF; font-family: 'JetBrains Mono', monospace; font-size:11px; color:#64748B;">
            // message #{{ $contactMessage->id }} · formulaire /contact
        </div>
    </div>
</body>
</html>
