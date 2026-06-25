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
