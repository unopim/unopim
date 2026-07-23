<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicering',
            'info'     => 'Offentligt serveringslager för publicerat, språkspecifikt innehåll.',
            'settings' => [
                'title'      => 'Publiceringsinställningar',
                'enabled'    => 'Aktiverad',
                'base-url'   => 'Bas-URL',
                'cache-ttl'  => 'Cache-TTL (sekunder)',
                'rate-limit' => 'Hastighetsgräns (förfrågningar/minut)',
                'indexable'  => 'Tillåt indexering av sökmotorer',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Utkast',
            'published' => 'Publicerad',
            'withdrawn' => 'Indragen',
            'redacted'  => 'Redigerad (dold)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Passet hittades inte.',
        ],
        '429' => [
            'heading' => 'För många förfrågningar. Försök igen om en stund.',
        ],
        'withdrawn' => [
            'heading' => 'Detta pass är inte längre tillgängligt.',
        ],
    ],
];
