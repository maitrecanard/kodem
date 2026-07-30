<?php

return [
    'premium' => [
        'price_cents' => (int) env('AUDITS_PREMIUM_PRICE_CENTS', 15000),
        'currency' => env('AUDITS_PREMIUM_CURRENCY', 'eur'),
        'product_name' => 'Rapport SEO & sécurité complet',
        'session_expires_minutes' => 30,

        // Le rapport est rédigé à la main, pas généré automatiquement : le délai
        // annoncé est un engagement commercial, il doit être affiché partout où
        // le prix l'est (page de vente, Stripe, e-mails, CGV).
        'delivery_hours' => '24 à 48 h',
        'manual' => true,

        // Réservé aux professionnels : pas de droit de rétractation à gérer,
        // mais la restriction doit être écrite noir sur blanc.
        'audience' => 'professionnels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Régime de TVA
    |--------------------------------------------------------------------------
    |
    | Franchise en base : aucune TVA n'est facturée, le prix affiché est le prix
    | payé. La mention de l'article 293 B du CGI est OBLIGATOIRE sur tout
    | document commercial. Ne jamais afficher « HT » dans ce régime : cela
    | laisserait croire qu'un montant s'ajoute au paiement.
    |
    */

    'vat' => [
        'applicable' => (bool) env('AUDITS_VAT_APPLICABLE', false),
        'rate_percent' => (float) env('AUDITS_VAT_RATE', 0),
        'legal_mention' => env('AUDITS_VAT_MENTION', 'TVA non applicable, art. 293 B du CGI'),
    ],
    'report_token_ttl_days' => 90,
    'admin_email' => env('AUDITS_ADMIN_EMAIL', 'admin@example.com'),
];
