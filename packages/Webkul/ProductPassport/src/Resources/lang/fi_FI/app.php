<?php

return [
    'type' => [
        'label' => 'Digitaalinen tuotepassi',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Tuotepassi',
            'info'     => 'Digitaalisen tuotepassin julkaisuasetukset.',
            'settings' => [
                'title'                  => 'Tuotepassin asetukset',
                'enabled'                => 'Käytössä',
                'auto-publish'           => 'Julkaise automaattisesti tallennettaessa',
                'completeness-threshold' => 'Täydellisyyden kynnysarvo (%)',
                'operator-name'          => 'Talouden toimijan nimi',
                'operator-address'       => 'Talouden toimijan osoite',
                'operator-eu-rep'        => 'EU:n valtuutettu edustaja',
                'support-url'            => 'Tuen URL-osoite',
            ],
        ],
    ],
];
