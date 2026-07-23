<?php

return [
    'type' => [
        'label' => 'Digitalt Produktpass',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpass',
            'info'     => 'Publiseringsinnstillinger for det digitale produktpasset.',
            'settings' => [
                'title'                  => 'Innstillinger for produktpass',
                'enabled'                => 'Aktivert',
                'auto-publish'           => 'Publiser automatisk ved lagring',
                'completeness-threshold' => 'Fullstendighetsterskel (%)',
                'operator-name'          => 'Navn på økonomisk aktør',
                'operator-address'       => 'Adresse til økonomisk aktør',
                'operator-eu-rep'        => 'EU-autorisert representant',
                'support-url'            => 'Support-URL',
            ],
        ],
    ],
];
