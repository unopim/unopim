<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Objava',
            'info'     => 'Javna razina posluživanja za objavljeni sadržaj, po jeziku.',
            'settings' => [
                'title'      => 'Postavke objave',
                'enabled'    => 'Omogućeno',
                'base-url'   => 'Osnovni URL',
                'cache-ttl'  => 'TTL predmemorije (sekunde)',
                'rate-limit' => 'Ograničenje brzine (zahtjeva/minuti)',
                'indexable'  => 'Dopusti indeksiranje tražilica',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Nacrt',
            'published' => 'Objavljeno',
            'withdrawn' => 'Povučeno',
            'redacted'  => 'Prikriveno',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Putovnica nije pronađena.',
        ],
        '429' => [
            'heading' => 'Previše zahtjeva. Pokušajte ponovno uskoro.',
        ],
        'withdrawn' => [
            'heading' => 'Ova putovnica više nije dostupna.',
        ],
    ],
];
