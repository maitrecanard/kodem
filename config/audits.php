<?php

return [
    'premium' => [
        'price_cents' => (int) env('AUDITS_PREMIUM_PRICE_CENTS', 14900),
        'currency' => env('AUDITS_PREMIUM_CURRENCY', 'eur'),
        'product_name' => 'Audit premium SEO + Sécurité',
        'session_expires_minutes' => 30,
    ],
    'report_token_ttl_days' => 90,
    'admin_email' => env('AUDITS_ADMIN_EMAIL', 'admin@example.com'),
];
