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
        'currencies' => [
            'title'      => 'Valutaer',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Valutakoden \'%s\' er allerede importeret i denne batch.',
                    'code-not-found-to-delete'    => 'Valuta med koden \'%s\' blev ikke fundet i systemet.',
                    'invalid-status'              => 'Status skal være 0 eller 1 (eller tom for standard aktiveret).',
                    'channel-related-locale-root' => 'Du kan ikke slette locale med koden :code, da det er tilknyttet en kanal.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roller',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplikeret rollenavn fundet.',
                    'name-not-found-to-delete' => 'Rolle med det angivne navn blev ikke fundet til sletning.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Brugere',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'Bruger med den angivne e-mail blev ikke fundet til sletning.',
                    'invalid-role'              => 'Ugyldigt rollenavn fundet.',
                    'invalid-locale'            => 'Ugyldig UI-lokalekode fundet.',
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
        'currencies' => [
            'title' => 'Valutaer',
        ],
        'roles' => [
            'title' => 'Roller',
        ],
        'users' => [
            'title'   => 'Brugere',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'all'    => 'Alle',
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
