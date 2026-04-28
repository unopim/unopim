<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL nøgle: \'%s\' blev allerede genereret for en vare med SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Ugyldig værdi for attributfamilie kolonne (attributfamilien eksisterer ikke?)',
                    'invalid-type'                             => 'Produkttypen er ugyldig eller ikke understøttet',
                    'sku-not-found'                            => 'Produkt med angivet SKU ikke fundet',
                    'super-attribute-not-found'                => 'Konfigurerbart attribut med kode: \'%s\' ikke fundet eller tilhører ikke attributfamilie: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Konfigurerbare attributter kræves for at oprette produktmodel',
                    'configurable-attributes-wrong-type'       => 'Kun valg af typen attributter, som ikke er lokation eller kanalbaserede, er tilladt som konfigurerbare attributter for en konfigurerbar produkt',
                    'variant-configurable-attribute-not-found' => 'Variant konfigurerbart attribut: :code er nødvendigt for at oprette',
                    'not-unique-variant-product'               => 'Produkt med samme konfigurerbare attributter eksisterer allerede.',
                    'channel-not-exist'                        => 'Dette kanal eksisterer ikke.',
                    'locale-not-in-channel'                    => 'Denne lokation er ikke valgt i kanalen.',
                    'locale-not-exist'                         => 'Denne lokation eksisterer ikke',
                    'not-unique-value'                         => 'Værdien :code skal være unik.',
                    'incorrect-family-for-variant'             => 'Familien skal være den samme som forældrefamilien',
                    'parent-not-exist'                         => 'Forælderen findes ikke.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategorier',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Du kan ikke slette rodkategorien, der er knyttet til en kanal',
                ],
            ],
        ],

        'category-fields' => [
            'title'      => 'Kategorifelter',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kategorifeltskode :code er allerede i brug.',
                    'code_not_found_to_delete' => 'Kategorifeltskode blev ikke fundet til sletning.',
                ],
            ],
        ],

        'attributes' => [
            'title'      => 'Attributter',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributkoden :code er allerede i brug.',
                    'code_not_found_to_delete'             => 'Attributkode blev ikke fundet til sletning.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattribut kan ikke slettes.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attributgrupper',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributgruppekoden :code er allerede i brug.',
                    'code_not_found_to_delete'             => 'Attributgruppekode blev ikke fundet til sletning.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattributgruppe kan ikke slettes.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attributfamilier',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributfamiliekoden :code er allerede i brug.',
                    'code_not_found_to_delete' => 'Attributfamiliekode blev ikke fundet til sletning.',
                    'invalid-attribute-group'  => 'Attributgruppen ":code" findes ikke.',
                    'invalid-attribute'        => 'Attributten ":code" findes ikke.',
                    'invalid-channel'          => 'Kanalen ":code" findes ikke.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attributindstillinger',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributindstillingskoden :code er allerede i brug.',
                    'code_not_found_to_delete' => 'Attributindstillingskode blev ikke fundet til sletning.',
                    'locale-not-exist'         => 'Sprog ":code" findes ikke.',
                    'invalid-attribute'        => 'Attributten ":code" findes ikke.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL nøgle: \'%s\' blev allerede genereret for en vare med SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Ugyldig værdi for attributfamilie kolonne (attributfamilien eksisterer ikke?)',
                    'invalid-type'              => 'Produkttypen er ugyldig eller ikke understøttet',
                    'sku-not-found'             => 'Produkt med angivet SKU ikke fundet',
                    'super-attribute-not-found' => 'Super attribut med kode: \'%s\' ikke fundet eller tilhører ikke attributfamilie: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorier',
        ],
        'category-fields' => [
            'title' => 'Kategorifelter',
        ],

        'attributes' => [
            'title' => 'Attributter',
        ],
        'attribute-groups' => [
            'title' => 'Attributgrupper',
        ],
        'attribute-families' => [
            'title' => 'Attributfamilier',
        ],
        'attribute-options' => [
            'title' => 'Attributindstillinger',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolonnenummer "%s" har tomme overskrifter.',
            'column-name-invalid'  => 'Ugyldige kolonnenavne: "%s".',
            'column-not-found'     => 'Krævede kolonner findes ikke: %s.',
            'column-numbers'       => 'Antallet af kolonner svarer ikke til antallet af rækker i overskriften.',
            'invalid-attribute'    => 'Overskriften indeholder ugyldige attributter: "%s".',
            'system'               => 'En uventet systemfejl opstod.',
            'wrong-quotes'         => 'Korte citationstegn blev brugt i stedet for lige citationstegn.',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Job udførelsen startet',
        'completed' => 'Job udførelsen afsluttet',
    ],
];
