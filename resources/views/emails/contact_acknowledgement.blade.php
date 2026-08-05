<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Votre message a bien été reçu</title></head>
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
            <div style="font-size:20px; font-weight:bold; margin-top:4px;">Votre message a bien été reçu</div>
        </div>
        <div style="padding:20px; font-size:14px; line-height:1.7;">
            <p>Bonjour {{ $contactMessage->name }},</p>
            <p>
                Merci pour votre message. Il est bien arrivé et je vous réponds
                personnellement sous 48 heures ouvrées.
            </p>

            <p style="margin-top:18px; color:#64748B;">Pour rappel, voici ce que vous avez envoyé :</p>
            <div style="margin-top:8px; padding:16px; background:#FBFCFE; border:1px solid #E8ECFF; border-radius:8px;">
                <div style="font-weight:600; margin-bottom:8px;">{{ $contactMessage->subject }}</div>
                <div style="white-space:pre-line; color:#64748B;">{{ $contactMessage->message }}</div>
            </div>

            <p style="margin-top:18px;">
                Inutile de renvoyer votre demande : si vous souhaitez ajouter une
                précision, répondez simplement à cet e-mail.
            </p>

            <p style="margin-top:18px;">Mathieu Siaudeau — KODEM</p>
        </div>
        <div style="padding:14px 20px; border-top:1px solid #E8ECFF; font-family: 'JetBrains Mono', monospace; font-size:11px; color:#64748B;">
            // réponse sous 48 h · devis gratuit · interlocuteur unique
        </div>
    </div>
</body>
</html>
