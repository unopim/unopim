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
        'product-associations' => [
            'title'      => 'Produktassociationer',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Feltet \'%s\' er påkrævet.',
                    'self-link-not-allowed'       => 'Produktet \'%s\' kan ikke tilknyttes sig selv.',
                    'sku-not-found'               => 'Produkt med SKU \'%s\' blev ikke fundet.',
                    'related-sku-not-found'       => 'Relateret produkt med SKU \'%s\' blev ikke fundet.',
                    'association-type-not-found'  => 'Associationstypen \'%s\' findes ikke eller er inaktiv.',
                    'invalid-field-value'         => 'Ugyldig værdi angivet for et associationsfelt.',
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
        'locales' => [
            'title'      => 'Sprog',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Sprogkoden \'%s\' er allerede importeret i denne batch.',
                    'code-not-found-to-delete'    => 'Sprog med koden \'%s\' blev ikke fundet i systemet.',
                    'invalid-status'              => 'Status skal være 0 eller 1 (eller tom for standard aktiveret).',
                    'channel-related-locale-root' => 'Du kan ikke slette sproget med koden :code, da det er knyttet til en kanal.',
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
            'title'   => 'Valutaer',
            'filters' => [
                'status' => 'Status',
                'enable' => 'Aktiver',
                'all'    => 'Alle',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status skal være 0 eller 1 (eller tom for standard aktiveret).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roller',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Brugere',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'all'    => 'Alle',
            ],
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
        'export-too-large' => 'Denne eksport er for stor til at køre: anslået :rows rækker × :columns kolonner (~:estimated) overstiger den tilgængelige plads (~:available). Indsnævr eksporten ved at vælge færre kanaler/sprog (og attributter), og prøv igen.',
        'fields'           => [
            'file-format'            => 'Filformat',
            'with-media'             => 'Med medier',
            'with-associations'      => 'Med tilknytninger',
            'with-associations-info' => 'Inkluder de gamle SKU-listekolonner (up_sells, cross_sells og related_products) i eksporten',
            'header-row'             => 'Header Row',
            'header-row-info'        => 'Write attribute codes as the first line',
            'use-labels'             => 'Use Labels',
            'use-labels-info'        => 'Export readable labels instead of codes',
            'date-format'            => 'Date Format',
            'date-format-options'    => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Filsti',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Aktiveret',
            'all'            => 'Alle',
        ],
        'products' => [
            'title'              => 'Produkter',
            'invalid-locales'    => 'Ikke alle valgte sprog er tilgængelige for de valgte kanaler.',
            'invalid-currencies' => 'Ikke alle valgte valutaer er tilgængelige for de valgte kanaler.',
            'filters'            => [
                'channels'             => 'Kanaler',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Valutaer',
                'currencies-info'      => 'Prisattributter eksporteres pr. valgt valuta. Lad feltet være tomt for at eksportere alle kanalvalutaer.',
                'locales'              => 'Sprog',
                'locales-info'         => 'Lokaliserbare attributter eksporteres én gang pr. valgt sprog. Lad feltet være tomt for at eksportere alle kanalsprog.',
                'attributes'           => 'Attributter',
                'attributes-info'      => 'Kun de valgte attributter eksporteres. Lad feltet være tomt for at eksportere alle attributter i familien.',
                'attribute-families'   => 'Attributfamilier',
                'categories'           => 'Kategorier',
                'completeness'         => 'Fuldstændighed',
                'completeness-options' => [
                    'none'         => 'Ingen betingelse for fuldstændighed',
                    'at-least-one' => 'Fuldstændig på mindst ét valgt sprog',
                    'all'          => 'Fuldstændig på alle valgte sprog',
                ],
                'time-condition' => 'Tidsbetingelse',
                'time-options'   => [
                    'none'              => 'Ingen datobetingelse',
                    'last-n-days'       => 'Produkter opdateret inden for de seneste N dage',
                    'between-dates'     => 'Produkter opdateret mellem to datoer',
                    'since-last-export' => 'Produkter opdateret siden sidste eksport',
                ],
                'time-value'     => 'Antal dage',
                'time-date'      => 'Startdato',
                'time-date-end'  => 'Slutdato',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Aktiveret',
                    'disable' => 'Deaktiveret',
                    'all'     => 'Alle',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identifikatorer',
                'identifiers-info' => 'Indsæt ét SKU / én identifikator pr. linje for kun at eksportere disse produkter. Lad feltet være tomt for at eksportere alle produkter.',
            ],
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
        'product-associations' => [
            'title' => 'Produktassociationer',
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
        'locales' => [
            'title' => 'Sprog',
        ],
        'channels' => [
            'title' => 'Kanaler',
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
