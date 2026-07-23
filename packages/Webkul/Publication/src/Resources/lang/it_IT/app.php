<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Pubblicazione',
            'info'     => 'Livello di distribuzione pubblico per contenuti pubblicati, per lingua.',
            'settings' => [
                'title'      => 'Impostazioni di pubblicazione',
                'enabled'    => 'Abilitato',
                'base-url'   => 'URL di base',
                'cache-ttl'  => 'TTL della cache (secondi)',
                'rate-limit' => 'Limite di frequenza (richieste/minuto)',
                'indexable'  => 'Consenti l\'indicizzazione dei motori di ricerca',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Bozza',
            'published' => 'Pubblicato',
            'withdrawn' => 'Ritirato',
            'redacted'  => 'Redatto',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaporto non trovato.',
        ],
        '429' => [
            'heading' => 'Troppe richieste. Riprova tra poco.',
        ],
        'withdrawn' => [
            'heading' => 'Questo passaporto non è più disponibile.',
        ],
    ],
];
