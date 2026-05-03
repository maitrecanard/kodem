<?php

return [
    /*
     * Prix (en centimes) du rapport d'audit complet.
     */
    'price_cents' => env('AUDIT_PRICE_CENTS', 2900),

    /*
     * Add-ons optionnels.
     */
    'pdf_price_cents' => env('AUDIT_PDF_PRICE_CENTS', 900),
    'cwv_price_cents' => env('AUDIT_CWV_PRICE_CENTS', 1900),

    /*
     * Abonnement monitoring (mensuel).
     */
    'monitoring_price_cents' => env('MONITORING_PRICE_CENTS', 4900),
    'monitoring_period_days' => env('MONITORING_PERIOD_DAYS', 30),
    'monitoring_alert_threshold' => env('MONITORING_ALERT_THRESHOLD', 10),

    /*
     * Intégration PageSpeed Insights (Google).
     * Laisser null fonctionne pour des petits volumes, sinon ajouter une clé.
     */
    'pagespeed_api_key' => env('GOOGLE_PAGESPEED_API_KEY'),
    'pagespeed_endpoint' => env(
        'GOOGLE_PAGESPEED_ENDPOINT',
        'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
    ),

    /*
     * Mode de paiement : "stub" (simulation) ou "stripe".
     */
    'payment_driver' => env('AUDIT_PAYMENT_DRIVER', 'stub'),
];
