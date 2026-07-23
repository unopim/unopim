<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Udgivelse',
            'info'     => 'Offentligt serveringslag for publiceret, sprogspecifikt indhold.',
            'settings' => [
                'title'      => 'Udgivelsesindstillinger',
                'enabled'    => 'Aktiveret',
                'base-url'   => 'Basis-URL',
                'cache-ttl'  => 'Cache-TTL (sekunder)',
                'rate-limit' => 'Hastighedsgrænse (forespørgsler/minut)',
                'indexable'  => 'Tillad indeksering fra søgemaskiner',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Kladde',
            'published' => 'Udgivet',
            'withdrawn' => 'Trukket tilbage',
            'redacted'  => 'Skjult',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Pas ikke fundet.',
        ],
        '429' => [
            'heading' => 'For mange anmodninger. Prøv venligst igen om lidt.',
        ],
        'withdrawn' => [
            'heading' => 'Dette pas er ikke længere tilgængeligt.',
        ],
    ],
];
