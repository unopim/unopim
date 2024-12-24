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
                    'super-attribute-not-found'                => 'Konfigurerbar attributt med kode: \'%s\' ikke funnet eller tilhører ikke attributtfamilie: \'%s\'',
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
        ],
    ],
    'job' => [
        'started'   => 'Jobbutførelse startet',
        'completed' => 'Jobbutførelse fullført',
    ],
];
