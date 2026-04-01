<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Proizvodi',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL ključ: \'%s\' već je generiran za stavku sa SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Neispravna vrijednost za kolonu obitelji atributa (obitelj atributa ne postoji?)',
                    'invalid-type'                             => 'Vrsta proizvoda je neispravna ili nije podržana',
                    'sku-not-found'                            => 'Proizvod sa specificiranim SKU-om nije pronađen',
                    'super-attribute-not-found'                => 'Configurable attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Konfigurabilni atributi su potrebni za kreiranje modela proizvoda',
                    'configurable-attributes-wrong-type'       => 'Samo atributi tipa \'select\' koji nisu vezani uz lokalizaciju ili kanal mogu biti konfigurabilni atributi za konfigurabilni proizvod',
                    'variant-configurable-attribute-not-found' => 'Variant configurable attribute: :code is required for creating',
                    'not-unique-variant-product'               => 'Proizvod sa istim konfigurabilnim atributima već postoji.',
                    'channel-not-exist'                        => 'Ovaj kanal ne postoji.',
                    'locale-not-in-channel'                    => 'Ova lokalizacija nije odabrana u kanalu.',
                    'locale-not-exist'                         => 'Ova lokalizacija ne postoji',
                    'not-unique-value'                         => 'Vrijednost :code mora biti jedinstvena.',
                    'incorrect-family-for-variant'             => 'Obitelj mora biti ista kao obitelj roditelja',
                    'parent-not-exist'                         => 'Roditelj ne postoji.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Kategorije',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Ne možete izbrisati korijensku kategoriju koja je povezana s kanalom',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Proizvodi',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL ključ: \'%s\' već je generiran za stavku sa SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Neispravna vrijednost za kolonu obitelji atributa (obitelj atributa ne postoji?)',
                    'invalid-type'              => 'Vrsta proizvoda je neispravna ili nije podržana',
                    'sku-not-found'             => 'Proizvod sa specificiranim SKU-om nije pronađen',
                    'super-attribute-not-found' => 'Super atribut sa kodom: \'%s\' nije pronađen ili ne pripada obitelji atributa: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Kategorije',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolone broj "%s" imaju prazne zaglavlja.',
            'column-name-invalid'  => 'Neispravna imena kolona: "%s".',
            'column-not-found'     => 'Potrebne kolone nisu pronađene: %s.',
            'column-numbers'       => 'Broj kolona ne odgovara broju redaka u zaglavlju.',
            'invalid-attribute'    => 'Zaglavlje sadrži neispravne atribut(e): "%s".',
            'system'               => 'Došlo je do neočekivane sistemske greške.',
            'wrong-quotes'         => 'Kovrčave navodnike koristite umjesto ravnih navodnika.',
        ],
    ],
    'job' => [
        'started'   => 'Izvršenje posla je započelo',
        'completed' => 'Izvršenje posla je završeno',
    ],
];
