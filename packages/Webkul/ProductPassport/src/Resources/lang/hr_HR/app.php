<?php

return [
    'type' => [
        'label' => 'Digitalna putovnica proizvoda',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Putovnica proizvoda',
            'info'     => 'Postavke objave digitalne putovnice proizvoda.',
            'settings' => [
                'title'                  => 'Postavke putovnice proizvoda',
                'enabled'                => 'Omogućeno',
                'auto-publish'           => 'Automatski objavi prilikom spremanja',
                'completeness-threshold' => 'Prag potpunosti (%)',
                'operator-name'          => 'Naziv gospodarskog subjekta',
                'operator-address'       => 'Adresa gospodarskog subjekta',
                'operator-eu-rep'        => 'Ovlašteni predstavnik u EU',
                'support-url'            => 'URL podrške',
            ],
        ],
    ],
];
