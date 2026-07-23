<?php

return [
    'type' => [
        'label' => 'Cyfrowy Paszport Produktu',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Paszport Produktu',
            'info'     => 'Ustawienia publikacji cyfrowego paszportu produktu.',
            'settings' => [
                'title'                  => 'Ustawienia paszportu produktu',
                'enabled'                => 'Włączone',
                'auto-publish'           => 'Publikuj automatycznie przy zapisie',
                'completeness-threshold' => 'Próg kompletności (%)',
                'operator-name'          => 'Nazwa podmiotu gospodarczego',
                'operator-address'       => 'Adres podmiotu gospodarczego',
                'operator-eu-rep'        => 'Upoważniony przedstawiciel w UE',
                'support-url'            => 'Adres URL wsparcia',
            ],
        ],
    ],
];
