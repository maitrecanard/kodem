<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destinataire des notifications du formulaire de contact
    |--------------------------------------------------------------------------
    |
    | Adresse qui reçoit chaque message soumis depuis /contact. Par défaut on
    | retombe sur l'adresse admin déjà utilisée par le module Audits, pour ne
    | pas dupliquer la configuration sur les installations existantes.
    |
    */

    'notify_email' => env('CONTACT_NOTIFY_EMAIL', env('AUDITS_ADMIN_EMAIL')),

    /*
    | Adresse supplémentaire en copie (associé, boîte partagée). Laisser vide
    | pour n'envoyer qu'au destinataire principal.
    */

    'notify_cc' => env('CONTACT_NOTIFY_CC'),

    /*
    | Accusé de réception envoyé au visiteur. Désactivable sans toucher au code
    | (utile en recette, ou si le volume de spam qui passe le honeypot augmente :
    | un accusé automatique répond alors à des adresses usurpées).
    */

    'send_acknowledgement' => (bool) env('CONTACT_SEND_ACK', true),

];
