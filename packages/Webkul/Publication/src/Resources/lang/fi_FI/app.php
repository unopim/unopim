<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Julkaisu',
            'info'     => 'Julkinen palvelutaso julkaistulle, kielikohtaiselle sisällölle.',
            'settings' => [
                'title'      => 'Julkaisuasetukset',
                'enabled'    => 'Käytössä',
                'base-url'   => 'Perus-URL',
                'cache-ttl'  => 'Välimuistin TTL (sekuntia)',
                'rate-limit' => 'Nopeusrajoitus (pyyntöä/minuutti)',
                'indexable'  => 'Salli hakukoneiden indeksointi',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Luonnos',
            'published' => 'Julkaistu',
            'withdrawn' => 'Peruutettu',
            'redacted'  => 'Peitetty',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Passia ei löytynyt.',
        ],
        '429' => [
            'heading' => 'Liikaa pyyntöjä. Yritä uudelleen hetken kuluttua.',
        ],
        'withdrawn' => [
            'heading' => 'Tämä passi ei ole enää saatavilla.',
        ],
    ],
];
