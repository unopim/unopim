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
        'category-fields' => [
            'title'      => 'Polja kategorije',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kod polja kategorije :code već je u upotrebi.',
                    'code_not_found_to_delete' => 'Kod polja kategorije nije pronađen za brisanje.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Značajke',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Kod značajke :code već se koristi.',
                    'code_not_found_to_delete'             => 'Kod značajke nije pronađen za brisanje.',
                    'code_is_system_and_cannot_be_deleted' => 'Značajka sustava ne može se izbrisati.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Grupe značajki',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Kod grupe značajki :code već se koristi.',
                    'code_not_found_to_delete'             => 'Kod grupe značajki nije pronađen za brisanje.',
                    'code_is_system_and_cannot_be_deleted' => 'Grupa značajki sustava ne može se izbrisati.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Obitelji značajki',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kod obitelji značajki :code već se koristi.',
                    'code_not_found_to_delete' => 'Kod obitelji značajki nije pronađen za brisanje.',
                    'invalid-attribute-group'  => 'Grupa značajki ":code" ne postoji.',
                    'invalid-attribute'        => 'Značajka ":code" ne postoji.',
                    'invalid-channel'          => 'Kanal ":code" ne postoji.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opcije značajki',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Kod opcije značajki :code već se koristi.',
                    'code_not_found_to_delete' => 'Kod opcije značajki nije pronađen za brisanje.',
                    'locale-not-exist'         => 'Lokalizacija ":code" ne postoji.',
                    'invalid-attribute'        => 'Značajka ":code" ne postoji.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanali',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanal s kodom :code nije pronađen za brisanje.',
                    'locale-not-found'         => 'Jedan ili više jezika ne postoje.',
                    'root-category-not-found'  => 'Glavna kategorija ne postoji.',
                    'currency-not-found'       => 'Jedna ili više valuta ne postoje.',
                    'invalid-locale'           => 'Jezik ne postoji.',
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
        'category-fields' => [
            'title' => 'Polja kategorije',
        ],
        'attributes' => [
            'title' => 'Značajke',
        ],
        'attribute-groups' => [
            'title' => 'Grupe značajki',
        ],
        'attribute-families' => [
            'title' => 'Obitelji značajki',
        ],
        'attribute-options' => [
            'title' => 'Opcije značajki',
        ],
        'channels' => [
            'title' => 'Kanali',
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
            'column-empty-headers' => 'Kolone broj "%s" imaju prazne zaglavlja.',
            'column-name-invalid'  => 'Neispravna imena kolona: "%s".',
            'column-not-found'     => 'Potrebne kolone nisu pronađene: %s.',
            'column-numbers'       => 'Broj kolona ne odgovara broju redaka u zaglavlju.',
            'invalid-attribute'    => 'Zaglavlje sadrži neispravne atribut(e): "%s".',
            'system'               => 'Došlo je do neočekivane sistemske greške.',
            'wrong-quotes'         => 'Kovrčave navodnike koristite umjesto ravnih navodnika.',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Izvršenje posla je započelo',
        'completed' => 'Izvršenje posla je završeno',
    ],
];
