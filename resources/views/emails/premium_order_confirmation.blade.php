<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation de votre commande</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #222;">
    <h1 style="color: #111;">Merci pour votre commande, {{ $auditRequest->name }} !</h1>

    <p>Nous avons bien reçu votre paiement pour l'audit premium du domaine
        <strong>{{ $auditRequest->domain }}</strong>.</p>

    <p>
        Montant : <strong>{{ number_format(($auditRequest->amount_cents ?? 0) / 100, 2, ',', ' ') }}
        {{ strtoupper((string) $auditRequest->currency) }}</strong>
    </p>

    <p>Votre rapport sera livré par email sous <strong>24 à 48 heures</strong>.</p>

    <p>Vous pourrez consulter votre rapport ici dès qu'il est prêt :</p>
    <p><a href="{{ route('audits.report', $auditRequest->access_token) }}">Accéder à mon rapport</a></p>

    <p style="margin-top: 32px; color: #666; font-size: 13px;">
        Référence : {{ $auditRequest->uuid }}
    </p>
</body>
</html>
