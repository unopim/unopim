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
        'channels' => [
            'title'      => 'Kanaler',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanal med koden :code blev ikke fundet til sletning.',
                    'locale-not-found'         => 'En eller flere sprog findes ikke.',
                    'root-category-not-found'  => 'Rodkategorien findes ikke.',
                    'currency-not-found'       => 'En eller flere valutaer findes ikke.',
                    'invalid-locale'           => 'Sproget findes ikke.',
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
            'column-empty-headers' => 'Kolonnenummer "%s" har tomme overskrifter.',
            'column-name-invalid'  => 'Ugyldige kolonnenavne: "%s".',
            'column-not-found'     => 'Krævede kolonner findes ikke: %s.',
            'column-numbers'       => 'Antallet af kolonner svarer ikke til antallet af rækker i overskriften.',
            'invalid-attribute'    => 'Overskriften indeholder ugyldige attributter: "%s".',
            'system'               => 'En uventet systemfejl opstod.',
            'wrong-quotes'         => 'Korte citationstegn blev brugt i stedet for lige citationstegn.',
            'file-empty'           => 'Filen er tom eller indeholder ingen headerrække. Upload venligst en gyldig fil med data.',
        ],
    ],
    'job' => [
        'started'   => 'Job udførelsen startet',
        'completed' => 'Job udførelsen afsluttet',
    ],
];
