<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicare',
            'info'     => 'Nivel de difuzare publică pentru conținut publicat, pe fiecare limbă.',
            'settings' => [
                'title'      => 'Setări de publicare',
                'enabled'    => 'Activat',
                'base-url'   => 'URL de bază',
                'cache-ttl'  => 'TTL cache (secunde)',
                'rate-limit' => 'Limită de rată (cereri/minut)',
                'indexable'  => 'Permite indexarea de către motoarele de căutare',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Ciornă',
            'published' => 'Publicat',
            'withdrawn' => 'Retras',
            'redacted'  => 'Cenzurat',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Pașaportul nu a fost găsit.',
        ],
        '429' => [
            'heading' => 'Prea multe cereri. Încercați din nou în scurt timp.',
        ],
        'withdrawn' => [
            'heading' => 'Acest pașaport nu mai este disponibil.',
        ],
    ],
];
