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
            'title' => 'Attributes',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute cannot be deleted.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute group code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute group code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute group cannot be deleted.',
                ],
            ],
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute family code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute family code not found for deletion.',
                    'invalid-attribute-group' => 'Attribute group ":code" does not exist.',
                    'invalid-attribute' => 'Attribute ":code" does not exist.',
                    'invalid-channel' => 'Channel ":code" does not exist.',
                ],
            ],
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
            'validation' => [
                'errors' => [
                    'duplicate-code' => 'Attribute option code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute option code not found for deletion.',
                    'locale-not-exist' => 'Locale ":code" does not exist.',
                    'invalid-attribute' => 'Attribute ":code" does not exist.',
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
            'title' => 'Attributes',
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
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
        ],
    ],
    'job' => [
        'started' => 'Jobbutførelse startet',
        'completed' => 'Jobbutførelse fullført',
    ],
];
