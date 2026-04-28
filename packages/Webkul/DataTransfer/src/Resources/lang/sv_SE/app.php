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
        'category-fields' => [
            'title'      => 'Kategorifält',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kategorifältskod :code används redan.',
                    'code_not_found_to_delete' => 'Kategorifältskod hittades inte för borttagning.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributkoden :code används redan.',
                    'code_not_found_to_delete'             => 'Attributkoden för borttagning hittades inte.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattributet kan inte tas bort.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attributgrupper',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributgruppskoden :code används redan.',
                    'code_not_found_to_delete'             => 'Attributgruppskoden för borttagning hittades inte.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattributgruppen kan inte tas bort.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attributfamiljer',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributfamiljkoden :code används redan.',
                    'code_not_found_to_delete' => 'Attributfamiljkoden för borttagning hittades inte.',
                    'invalid-attribute-group'  => 'Attributgruppen ":code" existerar inte.',
                    'invalid-attribute'        => 'Attributet ":code" existerar inte.',
                    'invalid-channel'          => 'Kanalen ":code" existerar inte.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attributalternativ',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributalternativkoden :code används redan.',
                    'code_not_found_to_delete' => 'Attributalternativkoden för borttagning hittades inte.',
                    'locale-not-exist'         => 'Språkinställningen ":code" existerar inte.',
                    'invalid-attribute'        => 'Attributet ":code" existerar inte.',
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
        'category-fields' => [
            'title' => 'Kategorifält',
        ],

        'attributes' => [
            'title' => 'Attribut',
        ],
        'attribute-groups' => [
            'title' => 'Attributgrupper',
        ],
        'attribute-families' => [
            'title' => 'Attributfamiljer',
        ],
        'attribute-options' => [
            'title' => 'Attributalternativ',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Jobbutförande påbörjades',
        'completed' => 'Jobbutförande slutfört',
    ],
];
