<?php

return [

    /*
    | Routes jamais appelées par route() dans le JavaScript : elles ne sont pas envoyées au
    | navigateur. La liste part dans le HTML de chaque page ; l'administration, les webhooks et
    | les routes techniques n'ont rien à y faire. Appeler route() sur l'une d'elles lève une
    | erreur explicite dès le développement.
    */

    'except' => [
        'admin.*',
        'sanctum.*',
        'storage.*',
        'stripe.*',
        'sitemap',
        'track',
    ],

];
