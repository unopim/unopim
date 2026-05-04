<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL-suffix: \'%s\' har redan skapats för en artikel med SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Ogiltigt värde för attributfamiljens kolumn (attributfamiljen finns inte?)',
                    'invalid-type'                             => 'Produktens typ är ogiltig eller inte stöds',
                    'sku-not-found'                            => 'Produkt med angiven SKU hittades inte',
                    'super-attribute-not-found'                => 'Konfigurerbart attribut med kod: \'%s\' hittades inte eller tillhör inte attributfamiljen: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Konfigurerbara attribut krävs för att skapa produktmodell',
                    'configurable-attributes-wrong-type'       => 'Endast attribut av typ som inte är baserade på plats eller kanal får vara konfigurerbara attribut för en konfigurerbar produkt',
                    'variant-configurable-attribute-not-found' => 'Variantkonfigurerbart attribut: :code krävs för att skapa',
                    'not-unique-variant-product'               => 'En produkt med samma konfigurerbara attribut finns redan.',
                    'channel-not-exist'                        => 'Denna kanal finns inte.',
                    'locale-not-in-channel'                    => 'Denna plats är inte vald i kanalen.',
                    'locale-not-exist'                         => 'Denna plats finns inte',
                    'not-unique-value'                         => 'Värdet :code måste vara unikt.',
                    'incorrect-family-for-variant'             => 'Familjen måste vara samma som huvudfamiljen',
                    'parent-not-exist'                         => 'Föräldern finns inte.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategorier',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Du kan inte radera rotkategorin som är associerad med en kanal',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roller',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicerat rollnamn hittades.',
                    'name-not-found-to-delete' => 'Roll med det angivna namnet hittades inte för borttagning.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Användare',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'Användare med angiven e-post hittades inte för borttagning.',
                    'invalid-role'              => 'Ogiltigt rollnamn hittades.',
                    'invalid-locale'            => 'Ogiltig UI-lokalkod hittades.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-suffix: \'%s\' har redan skapats för en artikel med SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Ogiltigt värde för attributfamiljens kolumn (attributfamiljen finns inte?)',
                    'invalid-type'              => 'Produktens typ är ogiltig eller inte stöds',
                    'sku-not-found'             => 'Produkt med angiven SKU hittades inte',
                    'super-attribute-not-found' => 'Konfigurerbart attribut med kod: \'%s\' hittades inte eller tillhör inte attributfamiljen: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorier',
        ],
        'roles' => [
            'title' => 'Roller',
        ],

        'users' => [
            'title'   => 'Användare',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'all'    => 'Alla',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolumner med nummer "%s" har tomma rubriker.',
            'column-name-invalid'  => 'Ogiltiga kolumnnamn: "%s".',
            'column-not-found'     => 'Krävda kolumner hittades inte: %s.',
            'column-numbers'       => 'Antalet kolumner överensstämmer inte med antalet rader i rubriken.',
            'invalid-attribute'    => 'Rubriken innehåller ogiltiga attribut: "%s".',
            'system'               => 'Ett oväntat systemfel inträffade.',
            'wrong-quotes'         => 'Krokiga citattecken användes i stället för raka citattecken.',
            'file-empty'           => 'Filen är tom eller innehåller ingen rubrikrad. Vänligen ladda upp en giltig fil med data.',
        ],
    ],
    'job' => [
        'started'   => 'Jobbutförande påbörjades',
        'completed' => 'Jobbutförande slutfört',
    ],
];
