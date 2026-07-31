<?php

return [
    'locales' => ['en_US', 'de_DE', 'fr_FR'],

    'currencies' => ['USD', 'EUR'],

    'channels' => [
        [
            'code'       => 'default',
            'root'       => 'root',
            'locales'    => ['en_US', 'de_DE', 'fr_FR'],
            'currencies' => ['USD', 'EUR'],
            'names'      => [
                'en_US' => 'Master Catalog',
                'de_DE' => 'Stammkatalog',
                'fr_FR' => 'Catalogue principal',
            ],
        ],
        [
            'code'       => 'ecommerce',
            'root'       => 'root',
            'locales'    => ['en_US', 'de_DE', 'fr_FR'],
            'currencies' => ['USD', 'EUR'],
            'names'      => [
                'en_US' => 'Online Store',
                'de_DE' => 'Onlineshop',
                'fr_FR' => 'Boutique en ligne',
            ],
        ],
        [
            'code'       => 'print',
            'root'       => 'root',
            'locales'    => ['en_US', 'de_DE'],
            'currencies' => ['EUR'],
            'names'      => [
                'en_US' => 'Print Catalog',
                'de_DE' => 'Printkatalog',
            ],
        ],
    ],
];
