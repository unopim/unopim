<?php

return [
    'importers' => [
        'products' => [
            'title' => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL-nøkkel: \'%s\' ble allerede generert for en vare med SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Ugyldig verdi for attributtfamiliekolonnen (attributtfamilien eksisterer ikke?)',
                    'invalid-type' => 'Produkttype er ugyldig eller ikke støttet',
                    'sku-not-found' => 'Produkt med spesifisert SKU ikke funnet',
                    'super-attribute-not-found' => 'Konfigurerbar attributt med kode: \'%s\' ikke funnet eller tilhører ikke attributtfamilie: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found' => 'Konfigurerbare attributter kreves for å lage produktmodell',
                    'configurable-attributes-wrong-type' => 'Bare typeattributter som ikke er basert på lokalt eller kanal kan brukes som konfigurerbare attributter for et konfigurerbart produkt',
                    'variant-configurable-attribute-not-found' => 'Variantkonfigurerbar attributt: :code kreves for å lage',
                    'not-unique-variant-product' => 'Et produkt med samme konfigurerbare attributter finnes allerede.',
                    'channel-not-exist' => 'Denne kanalen eksisterer ikke.',
                    'locale-not-in-channel' => 'Denne lokaliseringen er ikke valgt i kanalen.',
                    'locale-not-exist' => 'Denne lokaliseringen eksisterer ikke',
                    'not-unique-value' => ':code verdien må være unik.',
                    'incorrect-family-for-variant' => 'Familien må være den samme som foreldrefamilien',
                    'parent-not-exist' => 'Forelderen eksisterer ikke.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorier',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Du kan ikke slette roten kategori som er assosiert med en kanal',
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
    ],
    'exporters' => [
        'products' => [
            'title' => 'Produkter',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'URL-nøkkel: \'%s\' ble allerede generert for en vare med SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Ugyldig verdi for attributtfamiliekolonnen (attributtfamilien eksisterer ikke?)',
                    'invalid-type' => 'Produkttype er ugyldig eller ikke støttet',
                    'sku-not-found' => 'Produkt med spesifisert SKU ikke funnet',
                    'super-attribute-not-found' => 'Konfigurerbar attributt med kode: \'%s\' ikke funnet eller tilhører ikke attributtfamilie: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorier',
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
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolonner nummer "%s" har tomme overskrifter.',
            'column-name-invalid' => 'Ugyldige kolonnenavn: "%s".',
            'column-not-found' => 'Mangler nødvendige kolonner: %s.',
            'column-numbers' => 'Antall kolonner stemmer ikke med antall rader i overskriften.',
            'invalid-attribute' => 'Overskrift inneholder ugyldige attributter: "%s".',
            'system' => 'En uventet systemfeil oppstod.',
            'wrong-quotes' => 'Skråstilt anførselstegn brukt i stedet for rette anførselstegn.',
            'file-empty' => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started' => 'Jobbutførelse startet',
        'completed' => 'Jobbutførelse fullført',
    ],
];
