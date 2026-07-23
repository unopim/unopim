<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publisering',
            'info'     => 'Offentlig serveringslag for publisert, språkspesifikt innhold.',
            'settings' => [
                'title'      => 'Publiseringsinnstillinger',
                'enabled'    => 'Aktivert',
                'base-url'   => 'Grunn-URL',
                'cache-ttl'  => 'Cache-TTL (sekunder)',
                'rate-limit' => 'Hastighetsgrense (forespørsler/minutt)',
                'indexable'  => 'Tillat indeksering fra søkemotorer',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Utkast',
            'published' => 'Publisert',
            'withdrawn' => 'Trukket tilbake',
            'redacted'  => 'Sladdet',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Pass ikke funnet.',
        ],
        '429' => [
            'heading' => 'For mange forespørsler. Prøv igjen om litt.',
        ],
        'withdrawn' => [
            'heading' => 'Dette passet er ikke lenger tilgjengelig.',
        ],
    ],
];
