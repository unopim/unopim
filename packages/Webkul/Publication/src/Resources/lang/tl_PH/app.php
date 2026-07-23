<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Paglalathala',
            'info'     => 'Pampublikong antas ng paghahatid para sa nailathalang nilalaman, ayon sa bawat wika.',
            'settings' => [
                'title'      => 'Mga Setting ng Paglalathala',
                'enabled'    => 'Pinagana',
                'base-url'   => 'Batayang URL',
                'cache-ttl'  => 'TTL ng Cache (segundo)',
                'rate-limit' => 'Limitasyon sa Bilis (mga kahilingan/minuto)',
                'indexable'  => 'Payagan ang pag-index ng mga search engine',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Draft',
            'published' => 'Nailathala',
            'withdrawn' => 'Binawi',
            'redacted'  => 'Na-redact',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Hindi natagpuan ang pasaporte.',
        ],
        '429' => [
            'heading' => 'Masyadong maraming kahilingan. Pakisubukang muli sandali.',
        ],
        'withdrawn' => [
            'heading' => 'Hindi na available ang pasaporteng ito.',
        ],
    ],
];
