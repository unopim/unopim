<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Veröffentlichung',
            'info'     => 'Öffentliche Bereitstellungsebene für veröffentlichte, sprachspezifische Inhalte.',
            'settings' => [
                'title'      => 'Veröffentlichungseinstellungen',
                'enabled'    => 'Aktiviert',
                'base-url'   => 'Basis-URL',
                'cache-ttl'  => 'Cache-TTL (Sekunden)',
                'rate-limit' => 'Ratenbegrenzung (Anfragen/Minute)',
                'indexable'  => 'Indexierung durch Suchmaschinen zulassen',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Entwurf',
            'published' => 'Veröffentlicht',
            'withdrawn' => 'Zurückgezogen',
            'redacted'  => 'Geschwärzt',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Produktpass nicht gefunden.',
        ],
        '429' => [
            'heading' => 'Zu viele Anfragen. Bitte versuchen Sie es in Kürze erneut.',
        ],
        'withdrawn' => [
            'heading' => 'Dieser Produktpass ist nicht mehr verfügbar.',
        ],
    ],
];
