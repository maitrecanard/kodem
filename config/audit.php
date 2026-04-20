<?php

return [
    /*
     * Prix (en centimes) du rapport d'audit complet.
     * Surchargez via la variable d'environnement AUDIT_PRICE_CENTS.
     */
    'price_cents' => env('AUDIT_PRICE_CENTS', 2900),

    /*
     * Mode de paiement : "stub" (simulation — pour démo/dev) ou "stripe".
     */
    'payment_driver' => env('AUDIT_PAYMENT_DRIVER', 'stub'),
];
