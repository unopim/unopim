<?php

return [
    'type' => [
        'label' => 'Passaporto Digitale del Prodotto',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaporto del Prodotto',
            'info'     => 'Impostazioni di pubblicazione del passaporto digitale del prodotto.',
            'settings' => [
                'title'                  => 'Impostazioni del passaporto del prodotto',
                'enabled'                => 'Abilitato',
                'auto-publish'           => 'Pubblica automaticamente al salvataggio',
                'completeness-threshold' => 'Soglia di completezza (%)',
                'operator-name'          => 'Nome dell\'operatore economico',
                'operator-address'       => 'Indirizzo dell\'operatore economico',
                'operator-eu-rep'        => 'Rappresentante autorizzato nell\'UE',
                'support-url'            => 'URL di supporto',
            ],
        ],
    ],
];
