<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicatie',
            'info'     => 'Openbare serveerlaag voor gepubliceerde, taalspecifieke inhoud.',
            'settings' => [
                'title'      => 'Publicatie-instellingen',
                'enabled'    => 'Ingeschakeld',
                'base-url'   => 'Basis-URL',
                'cache-ttl'  => 'Cache-TTL (seconden)',
                'rate-limit' => 'Snelheidslimiet (verzoeken/minuut)',
                'indexable'  => 'Indexering door zoekmachines toestaan',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Concept',
            'published' => 'Gepubliceerd',
            'withdrawn' => 'Ingetrokken',
            'redacted'  => 'Bewerkt (geredigeerd)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Paspoort niet gevonden.',
        ],
        '429' => [
            'heading' => 'Te veel verzoeken. Probeer het straks opnieuw.',
        ],
        'withdrawn' => [
            'heading' => 'Dit paspoort is niet langer beschikbaar.',
        ],
    ],
];
