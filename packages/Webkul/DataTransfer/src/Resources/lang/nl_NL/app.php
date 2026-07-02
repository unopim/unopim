<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Producten',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'url-sleutel: \'%s\' is al gegenereerd voor een item met de SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Ongeldige waarde voor de attribuutfamiliekolom (attribuutfamilie bestaat niet?)',
                    'invalid-type'                             => 'Producttype is ongeldig of wordt niet ondersteund',
                    'sku-not-found'                            => 'Product met opgegeven SKU niet gevonden',
                    'super-attribute-not-found'                => 'Configureerbaar attribuut met code :code niet gevonden of behoort niet tot de attribuutfamilie :familyCode',
                    'configurable-attributes-not-found'        => 'Configureerbare attributen zijn vereist voor het maken van een productmodel',
                    'configurable-attributes-wrong-type'       => 'Alleen typekenmerken selecteren die niet op landinstellingen of kanalen zijn gebaseerd, mogen configureerbare kenmerken zijn voor een configureerbaar product',
                    'variant-configurable-attribute-not-found' => 'Variant configureerbaar attribuut :code is vereist voor het maken',
                    'not-unique-variant-product'               => 'Er bestaat al een product met dezelfde configureerbare kenmerken.',
                    'channel-not-exist'                        => 'Dit kanaal bestaat niet.',
                    'locale-not-in-channel'                    => 'Deze landinstelling is niet geselecteerd in het kanaal.',
                    'locale-not-exist'                         => 'Deze landinstelling bestaat niet',
                    'not-unique-value'                         => 'De :code waarde moet uniek zijn.',
                    'incorrect-family-for-variant'             => 'Het gezin moet hetzelfde zijn als het oudergezin',
                    'parent-not-exist'                         => 'De ouder bestaat niet.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorieën',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'U kunt de hoofdcategorie die aan een kanaal is gekoppeld, niet verwijderen',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Categorievelden',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'De code van het categorieveld :code is al in gebruik.',
                    'code_not_found_to_delete' => 'De code van het categorieveld is niet gevonden om te verwijderen.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attributen',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attribuutcode :code is al in gebruik.',
                    'code_not_found_to_delete'             => 'Attribuutcode niet gevonden voor verwijdering.',
                    'code_is_system_and_cannot_be_deleted' => 'Systeemattribuut kan niet worden verwijderd.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attribuutgroepen',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attribuutgroepcode :code is al in gebruik.',
                    'code_not_found_to_delete'             => 'Attribuutgroepcode niet gevonden voor verwijdering.',
                    'code_is_system_and_cannot_be_deleted' => 'Systeemattribuutgroep kan niet worden verwijderd.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attribuutfamilies',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attribuutfamiliecode :code is al in gebruik.',
                    'code_not_found_to_delete' => 'Attribuutfamiliecode niet gevonden voor verwijdering.',
                    'invalid-attribute-group'  => 'Attribuutgroep ":code" bestaat niet.',
                    'invalid-attribute'        => 'Attribuut ":code" bestaat niet.',
                    'invalid-channel'          => 'Kanaal ":code" bestaat niet.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attribuutopties',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attribuutoptiecode :code is al in gebruik.',
                    'code_not_found_to_delete' => 'Attribuutoptiecode niet gevonden voor verwijdering.',
                    'locale-not-exist'         => 'Landinstelling ":code" bestaat niet.',
                    'invalid-attribute'        => 'Attribuut ":code" bestaat niet.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Talen',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'De taalcode \'%s\' is al geïmporteerd in deze batch.',
                    'code-not-found-to-delete'    => 'Taal met code \'%s\' niet gevonden in het systeem.',
                    'invalid-status'              => 'Status moet 0 of 1 zijn (of leeg voor standaard ingeschakeld).',
                    'channel-related-locale-root' => 'Je kunt de taal met code :code niet verwijderen omdat deze is gekoppeld aan een kanaal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanalen',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanaal met code :code niet gevonden om te verwijderen.',
                    'locale-not-found'         => 'Een of meer talen bestaan niet.',
                    'root-category-not-found'  => 'De hoofdcategorie bestaat niet.',
                    'currency-not-found'       => 'Een of meer valuta bestaan niet.',
                    'invalid-locale'           => 'De taal bestaat niet.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Currencies',
            'filters' => [
                'status' => 'Status',
                'enable' => 'Inschakelen',
                'all'    => 'Alle',
            ],
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
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Actief',
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
        'export-too-large' => 'Deze export is te groot om uit te voeren: naar schatting :rows rijen × :columns kolommen (~:estimated) overschrijden de beschikbare ruimte (~:available). Beperk de export door minder kanalen/talen (en attributen) te selecteren en probeer het opnieuw.',
        'fields'           => [
            'file-format'         => 'Bestandsformaat',
            'with-media'          => 'Met media',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Ingeschakeld',
            'all'            => 'Alle',
        ],
        'products' => [
            'title'              => 'Producten',
            'invalid-locales'    => 'Niet alle geselecteerde talen zijn beschikbaar voor de geselecteerde kanalen.',
            'invalid-currencies' => 'Niet alle geselecteerde valuta\'s zijn beschikbaar voor de geselecteerde kanalen.',
            'filters'            => [
                'channels'             => 'Kanalen',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Valuta\'s',
                'currencies-info'      => 'Prijsattributen worden per geselecteerde valuta geëxporteerd. Laat leeg om alle kanaalvaluta\'s te exporteren.',
                'locales'              => 'Talen',
                'locales-info'         => 'Lokaliseerbare attributen worden eenmaal per geselecteerde taal geëxporteerd. Laat leeg om alle kanaaltalen te exporteren.',
                'attributes'           => 'Attributen',
                'attributes-info'      => 'Alleen de geselecteerde attributen worden geëxporteerd. Laat leeg om alle attributen in de familie te exporteren.',
                'attribute-families'   => 'Attribuutfamilies',
                'categories'           => 'Categorieën',
                'completeness'         => 'Volledigheid',
                'completeness-options' => [
                    'none'         => 'Geen voorwaarde voor volledigheid',
                    'at-least-one' => 'Volledig in ten minste één geselecteerde taal',
                    'all'          => 'Volledig in alle geselecteerde talen',
                ],
                'time-condition' => 'Tijdvoorwaarde',
                'time-options'   => [
                    'none'              => 'Geen datumvoorwaarde',
                    'last-n-days'       => 'Producten bijgewerkt in de laatste N dagen',
                    'between-dates'     => 'Producten bijgewerkt tussen twee datums',
                    'since-last-export' => 'Producten bijgewerkt sinds de laatste export',
                ],
                'time-value'     => 'Aantal dagen',
                'time-date'      => 'Startdatum',
                'time-date-end'  => 'Einddatum',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Ingeschakeld',
                    'disable' => 'Uitgeschakeld',
                    'all'     => 'Alle',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identificatoren',
                'identifiers-info' => 'Plak één SKU / identificatie per regel om alleen die producten te exporteren. Laat leeg om alle producten te exporteren.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-sleutel: \'%s\' is al gegenereerd voor een item met de SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Ongeldige waarde voor de attribuutfamiliekolom (attribuutfamilie bestaat niet?)',
                    'invalid-type'              => 'Producttype is ongeldig of wordt niet ondersteund',
                    'sku-not-found'             => 'Product met opgegeven SKU niet gevonden',
                    'super-attribute-not-found' => 'Superattribuut met code: \'%s\' niet gevonden of behoort niet tot de attribuutfamilie: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorieën',
        ],
        'category-fields' => [
            'title' => 'Categorievelden',
        ],
        'attributes' => [
            'title' => 'Attributen',
        ],
        'attribute-groups' => [
            'title' => 'Attribuutgroepen',
        ],
        'attribute-families' => [
            'title' => 'Attribuutfamilies',
        ],
        'attribute-options' => [
            'title' => 'Attribuutopties',
        ],
        'locales' => [
            'title' => 'Talen',
        ],
        'channels' => [
            'title' => 'Kanalen',
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
            'column-empty-headers' => 'Kolommen nummer "%s" hebben lege kopteksten.',
            'column-name-invalid'  => 'Ongeldige kolomnamen: "%s".',
            'column-not-found'     => 'Vereiste kolommen niet gevonden: %s.',
            'column-numbers'       => 'Het aantal kolommen komt niet overeen met het aantal rijen in de koptekst.',
            'invalid-attribute'    => 'Header bevat ongeldig(e) attribuut(en): "%s".',
            'system'               => 'Er heeft zich een onverwachte systeemfout voorgedaan.',
            'wrong-quotes'         => 'Er worden gekrulde aanhalingstekens gebruikt in plaats van rechte aanhalingstekens.',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'De uitvoering van de werkzaamheden is begonnen',
        'completed' => 'Uitvoering van de taak voltooid',
    ],
];
