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
        'locales' => [
            'title'      => 'Språk',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Språkkoden \'%s\' har redan importerats i denna batch.',
                    'code-not-found-to-delete'    => 'Språk med koden \'%s\' hittades inte i systemet.',
                    'invalid-status'              => 'Status måste vara 0 eller 1 (eller tom för standard aktiverad).',
                    'channel-related-locale-root' => 'Du kan inte ta bort språket med kod :code eftersom det är kopplat till en kanal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanaler',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanal med kod :code hittades inte för borttagning.',
                    'locale-not-found'         => 'Ett eller flera språk finns inte.',
                    'root-category-not-found'  => 'Rotkategori finns inte.',
                    'currency-not-found'       => 'En eller flera valutor finns inte.',
                    'invalid-locale'           => 'Språket finns inte.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
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
        'locales' => [
            'title' => 'Språk',
        ],
        'channels' => [
            'title' => 'Kanaler',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
