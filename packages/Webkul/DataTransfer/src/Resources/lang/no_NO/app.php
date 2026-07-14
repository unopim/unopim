<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL-nøkkel: \'%s\' ble allerede generert for en vare med SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Ugyldig verdi for attributtfamiliekolonnen (attributtfamilien eksisterer ikke?)',
                    'invalid-type'                             => 'Produkttype er ugyldig eller ikke støttet',
                    'sku-not-found'                            => 'Produkt med spesifisert SKU ikke funnet',
                    'super-attribute-not-found'                => 'Konfigurerbar attributt med kode: \'%s\' ikke funnet eller tilhører ikke attributtfamilie: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Konfigurerbare attributter kreves for å lage produktmodell',
                    'configurable-attributes-wrong-type'       => 'Bare typeattributter som ikke er basert på lokalt eller kanal kan brukes som konfigurerbare attributter for et konfigurerbart produkt',
                    'variant-configurable-attribute-not-found' => 'Variantkonfigurerbar attributt: :code kreves for å lage',
                    'not-unique-variant-product'               => 'Et produkt med samme konfigurerbare attributter finnes allerede.',
                    'channel-not-exist'                        => 'Denne kanalen eksisterer ikke.',
                    'locale-not-in-channel'                    => 'Denne lokaliseringen er ikke valgt i kanalen.',
                    'locale-not-exist'                         => 'Denne lokaliseringen eksisterer ikke',
                    'not-unique-value'                         => ':code verdien må være unik.',
                    'incorrect-family-for-variant'             => 'Familien må være den samme som foreldrefamilien',
                    'parent-not-exist'                         => 'Forelderen eksisterer ikke.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategorier',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Du kan ikke slette roten kategori som er assosiert med en kanal',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Kategorifelter',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kategorifeltskode :code er allerede i bruk.',
                    'code_not_found_to_delete' => 'Kategorifeltskode ble ikke funnet for sletting.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attributter',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributtkoden :code er allerede i bruk.',
                    'code_not_found_to_delete'             => 'Attributtkoden ble ikke funnet for sletting.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattributt kan ikke slettes.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attributtgrupper',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributtgruppekoden :code er allerede i bruk.',
                    'code_not_found_to_delete'             => 'Attributtgruppekoden ble ikke funnet for sletting.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattributtgruppe kan ikke slettes.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attributtfamilier',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributtfamiliekoden :code er allerede i bruk.',
                    'code_not_found_to_delete' => 'Attributtfamiliekoden ble ikke funnet for sletting.',
                    'invalid-attribute-group'  => 'Attributtgruppen ":code" eksisterer ikke.',
                    'invalid-attribute'        => 'Attributtet ":code" eksisterer ikke.',
                    'invalid-channel'          => 'Kanalen ":code" eksisterer ikke.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attributtalternativer',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributtalternativkoden :code er allerede i bruk.',
                    'code_not_found_to_delete' => 'Attributtalternativkoden ble ikke funnet for sletting.',
                    'locale-not-exist'         => 'Språket ":code" eksisterer ikke.',
                    'invalid-attribute'        => 'Attributtet ":code" eksisterer ikke.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Språk',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Språkkoden \'%s\' er allerede importert i denne batchen.',
                    'code-not-found-to-delete'    => 'Språk med koden \'%s\' ble ikke funnet i systemet.',
                    'invalid-status'              => 'Status må være 0 eller 1 (eller tom for standard aktivert).',
                    'channel-related-locale-root' => 'Du kan ikke slette språket med koden :code fordi det er knyttet til en kanal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanaler',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanal med kode :code ble ikke funnet for sletting.',
                    'locale-not-found'         => 'Ett eller flere språk finnes ikke.',
                    'root-category-not-found'  => 'Rotkategori finnes ikke.',
                    'currency-not-found'       => 'En eller flere valutaer finnes ikke.',
                    'invalid-locale'           => 'Språket finnes ikke.',
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
                    'invalid-status'              => 'Status må være 0 eller 1 (eller tom for standard aktivert).',
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
            'title'   => 'Brukere',
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
        'export-too-large' => 'Denne eksporten er for stor til å kjøre: anslåtte :rows rader × :columns kolonner (~:estimated) overskrider tilgjengelig plass (~:available). Begrens eksporten ved å velge færre kanaler/språk (og attributter), og prøv igjen.',
        'fields'           => [
            'file-format'         => 'Filformat',
            'with-media'          => 'Med medier',
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
            'file-path'      => 'Filsti',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Aktivert',
            'all'            => 'Alle',
        ],
        'products' => [
            'title'              => 'Produkter',
            'invalid-locales'    => 'Ikke alle valgte språk er tilgjengelige for de valgte kanalene.',
            'invalid-currencies' => 'Ikke alle valgte valutaer er tilgjengelige for de valgte kanalene.',
            'filters'            => [
                'channels'             => 'Kanaler',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Valutaer',
                'currencies-info'      => 'Prisattributter eksporteres per valgt valuta. La feltet stå tomt for å eksportere alle kanalvalutaer.',
                'locales'              => 'Språk',
                'locales-info'         => 'Lokaliserbare attributter eksporteres én gang per valgt språk. La feltet stå tomt for å eksportere alle kanalspråk.',
                'attributes'           => 'Attributter',
                'attributes-info'      => 'Bare de valgte attributtene eksporteres. La feltet stå tomt for å eksportere alle attributter i familien.',
                'attribute-families'   => 'Attributtfamilier',
                'categories'           => 'Kategorier',
                'completeness'         => 'Fullstendighet',
                'completeness-options' => [
                    'none'         => 'Ingen betingelse for fullstendighet',
                    'at-least-one' => 'Fullstendig på minst ett valgt språk',
                    'all'          => 'Fullstendig på alle valgte språk',
                ],
                'time-condition' => 'Tidsbetingelse',
                'time-options'   => [
                    'none'              => 'Ingen datobetingelse',
                    'last-n-days'       => 'Produkter oppdatert de siste N dagene',
                    'between-dates'     => 'Produkter oppdatert mellom to datoer',
                    'since-last-export' => 'Produkter oppdatert siden forrige eksport',
                ],
                'time-value'     => 'Antall dager',
                'time-date'      => 'Startdato',
                'time-date-end'  => 'Sluttdato',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Aktivert',
                    'disable' => 'Deaktivert',
                    'all'     => 'Alle',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identifikatorer',
                'identifiers-info' => 'Lim inn én SKU / identifikator per linje for å eksportere bare disse produktene. La feltet stå tomt for å eksportere alle produkter.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-nøkkel: \'%s\' ble allerede generert for en vare med SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Ugyldig verdi for attributtfamiliekolonnen (attributtfamilien eksisterer ikke?)',
                    'invalid-type'              => 'Produkttype er ugyldig eller ikke støttet',
                    'sku-not-found'             => 'Produkt med spesifisert SKU ikke funnet',
                    'super-attribute-not-found' => 'Konfigurerbar attributt med kode: \'%s\' ikke funnet eller tilhører ikke attributtfamilie: \'%s\'',
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
            'title' => 'Attributtgrupper',
        ],
        'attribute-families' => [
            'title' => 'Attributtfamilier',
        ],
        'attribute-options' => [
            'title' => 'Attributtalternativer',
        ],
        'locales' => [
            'title' => 'Språk',
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
            'title'   => 'Brukere',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'all'    => 'Alle',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolonner nummer "%s" har tomme overskrifter.',
            'column-name-invalid'  => 'Ugyldige kolonnenavn: "%s".',
            'column-not-found'     => 'Mangler nødvendige kolonner: %s.',
            'column-numbers'       => 'Antall kolonner stemmer ikke med antall rader i overskriften.',
            'invalid-attribute'    => 'Overskrift inneholder ugyldige attributter: "%s".',
            'system'               => 'En uventet systemfeil oppstod.',
            'wrong-quotes'         => 'Skråstilt anførselstegn brukt i stedet for rette anførselstegn.',
            'file-empty'           => 'Filen er tom eller inneholder ingen overskriftsrad. Vennligst last opp en gyldig fil med data.',
        ],
    ],
    'job' => [
        'started'   => 'Jobbutførelse startet',
        'completed' => 'Jobbutførelse fullført',
    ],
];
