<?php

return [
    'warning' => [
        'title'           => 'Nepotrivire APP_URL detectată',
        'dismiss'         => 'Închide',
        'lede-before'     => 'Resursele frontend (CSS, JS) sunt fixate la valoarea configurată',
        'lede-after'      => 'Actualizați-o pentru a corespunde gazdei pe care o utilizați, altfel stilurile și scripturile nu se vor încărca.',
        'configured-env'  => 'Configurat (.env)',
        'mismatch-tag'    => 'NEPOTRIVIRE',
        'actual-browser'  => 'Actual (browser)',
        'in-use-tag'      => 'ÎN UZ',
        'toggle-step'     => 'Comută pasul :number',
        'step-1-title'    => 'Actualizați APP_URL în fișierul .env',
        'step-1-hint'     => 'Deschideți fișierul .env al proiectului și înlocuiți linia APP_URL.',
        'step-2-title'    => 'Goliți cache-ul aplicației',
        'step-2-hint'     => 'Rulați aceasta în terminal din rădăcina proiectului.',
        'copy'            => 'Copiază',
        'copied'          => 'Copiat',
        'note-bold'       => 'Apoi reîmprospătați forțat pagina',
        'note-rest'       => 'astfel încât browserul să reîncarce resursele actualizate.',
        'progress'        => ':done din :total pași finalizați',
        'all-done'        => 'Gata',
        'powered-by'      => 'Susținut de',
        'open-source-by'  => 'Un proiect open-source realizat de',
        'copied-toast'    => 'Copiat în clipboard',
        'still-mismatch'  => 'APP_URL tot nu corespunde. Actualizați .env și rulați "php artisan optimize:clear".',
        'verify-failed'   => 'Nu s-a putut verifica APP_URL. Vă rugăm să reîmprospătați pagina.',
        'logged-out'      => 'Deconectat: APP_URL nu corespunde gazdei curente. Actualizați APP_URL în .env și rulați "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Nepotrivire APP_URL detectată',
        'hint'     => 'Actualizați APP_URL în .env la URL-ul cererii, apoi rulați: php artisan optimize:clear',
    ],
];
