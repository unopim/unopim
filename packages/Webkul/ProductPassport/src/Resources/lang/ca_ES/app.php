<?php

return [
    'type' => [
        'label' => 'Passaport Digital del Producte',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaport del Producte',
            'info'     => 'Configuració de publicació del Passaport Digital del Producte.',
            'settings' => [
                'title'                  => 'Configuració del passaport del producte',
                'enabled'                => 'Activat',
                'auto-publish'           => 'Publica automàticament en desar',
                'completeness-threshold' => 'Llindar de compleció (%)',
                'operator-name'          => 'Nom de l\'operador econòmic',
                'operator-address'       => 'Adreça de l\'operador econòmic',
                'operator-eu-rep'        => 'Representant autoritzat a la UE',
                'support-url'            => 'URL de suport',
            ],
        ],
    ],
];
