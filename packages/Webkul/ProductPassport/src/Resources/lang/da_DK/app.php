<?php

return [
    'type' => [
        'label' => 'Digitalt Produktpas',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpas',
            'info'     => 'Indstillinger for offentliggørelse af det digitale produktpas.',
            'settings' => [
                'title'                  => 'Indstillinger for produktpas',
                'enabled'                => 'Aktiveret',
                'auto-publish'           => 'Udgiv automatisk ved gemning',
                'completeness-threshold' => 'Fuldstændighedsgrænse (%)',
                'operator-name'          => 'Økonomisk operatørs navn',
                'operator-address'       => 'Økonomisk operatørs adresse',
                'operator-eu-rep'        => 'EU-autoriseret repræsentant',
                'support-url'            => 'Support-URL',
            ],
        ],
    ],
];
