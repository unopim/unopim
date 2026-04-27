<?php

return [
    'importers' => [
        'products' => [
            'title' => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL-suffix: \'%s\' har redan skapats för en artikel med SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Ogiltigt värde för attributfamiljens kolumn (attributfamiljen finns inte?)',
                    'invalid-type' => 'Produktens typ är ogiltig eller inte stöds',
                    'sku-not-found' => 'Produkt med angiven SKU hittades inte',
                    'super-attribute-not-found' => 'Konfigurerbart attribut med kod: \'%s\' hittades inte eller tillhör inte attributfamiljen: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found' => 'Konfigurerbara attribut krävs för att skapa produktmodell',
                    'configurable-attributes-wrong-type' => 'Endast attribut av typ som inte är baserade på plats eller kanal får vara konfigurerbara attribut för en konfigurerbar produkt',
                    'variant-configurable-attribute-not-found' => 'Variantkonfigurerbart attribut: :code krävs för att skapa',
                    'not-unique-variant-product' => 'En produkt med samma konfigurerbara attribut finns redan.',
                    'channel-not-exist' => 'Denna kanal finns inte.',
                    'locale-not-in-channel' => 'Denna plats är inte vald i kanalen.',
                    'locale-not-exist' => 'Denna plats finns inte',
                    'not-unique-value' => 'Värdet :code måste vara unikt.',
                    'incorrect-family-for-variant' => 'Familjen måste vara samma som huvudfamiljen',
                    'parent-not-exist' => 'Föräldern finns inte.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorier',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Du kan inte radera rotkategorin som är associerad med en kanal',
                ],
            ],
        ],
        'attributes' => [
            'title' => 'Attributes',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute cannot be deleted.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute group code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute group code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute group cannot be deleted.',
                ],
            ],
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute family code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute family code not found for deletion.',
                    'invalid-attribute-group' => 'Attribute group ":code" does not exist.',
                    'invalid-attribute' => 'Attribute ":code" does not exist.',
                    'invalid-channel' => 'Channel ":code" does not exist.',
                ],
            ],
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute option code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute option code not found for deletion.',
                    'locale-not-exist' => 'Locale ":code" does not exist.',
                    'invalid-attribute' => 'Attribute ":code" does not exist.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title' => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL-suffix: \'%s\' har redan skapats för en artikel med SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Ogiltigt värde för attributfamiljens kolumn (attributfamiljen finns inte?)',
                    'invalid-type' => 'Produktens typ är ogiltig eller inte stöds',
                    'sku-not-found' => 'Produkt med angiven SKU hittades inte',
                    'super-attribute-not-found' => 'Konfigurerbart attribut med kod: \'%s\' hittades inte eller tillhör inte attributfamiljen: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorier',
        ],
        'attributes' => [
            'title' => 'Attributes',
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolumner med nummer "%s" har tomma rubriker.',
            'column-name-invalid' => 'Ogiltiga kolumnnamn: "%s".',
            'column-not-found' => 'Krävda kolumner hittades inte: %s.',
            'column-numbers' => 'Antalet kolumner överensstämmer inte med antalet rader i rubriken.',
            'invalid-attribute' => 'Rubriken innehåller ogiltiga attribut: "%s".',
            'system' => 'Ett oväntat systemfel inträffade.',
            'wrong-quotes' => 'Krokiga citattecken användes i stället för raka citattecken.',
            'file-empty' => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started' => 'Jobbutförande påbörjades',
        'completed' => 'Jobbutförande slutfört',
    ],
];
