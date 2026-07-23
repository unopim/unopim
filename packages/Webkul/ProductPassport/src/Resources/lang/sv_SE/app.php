<?php

return [
    'type' => [
        'label' => 'Digitalt Produktpass',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpass',
            'info'     => 'Publiceringsinställningar för det digitala produktpasset.',
            'settings' => [
                'title'                  => 'Inställningar för produktpass',
                'enabled'                => 'Aktiverad',
                'auto-publish'           => 'Publicera automatiskt vid sparande',
                'completeness-threshold' => 'Fullständighetströskel (%)',
                'operator-name'          => 'Namn på ekonomisk aktör',
                'operator-address'       => 'Adress till ekonomisk aktör',
                'operator-eu-rep'        => 'Auktoriserad representant i EU',
                'support-url'            => 'Support-URL',
            ],
        ],
    ],
];
