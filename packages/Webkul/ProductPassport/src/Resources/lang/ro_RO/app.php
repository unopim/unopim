<?php

return [
    'type' => [
        'label' => 'Pașaport Digital al Produsului',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Pașaportul Produsului',
            'info'     => 'Setările de publicare a pașaportului digital al produsului.',
            'settings' => [
                'title'                  => 'Setări pașaport produs',
                'enabled'                => 'Activat',
                'auto-publish'           => 'Publică automat la salvare',
                'completeness-threshold' => 'Prag de completitudine (%)',
                'operator-name'          => 'Numele operatorului economic',
                'operator-address'       => 'Adresa operatorului economic',
                'operator-eu-rep'        => 'Reprezentant autorizat în UE',
                'support-url'            => 'URL de asistență',
            ],
        ],
    ],
];
