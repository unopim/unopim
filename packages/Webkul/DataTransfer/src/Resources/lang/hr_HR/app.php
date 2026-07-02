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
        'locales' => [
            'title'      => 'Jezici',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Kod jezika \'%s\' već je uvezen u ovom paketu.',
                    'code-not-found-to-delete'    => 'Jezik s kodom \'%s\' nije pronađen u sustavu.',
                    'invalid-status'              => 'Status mora biti 0 ili 1 (ili prazno za zadano omogućeno).',
                    'channel-related-locale-root' => 'Ne možete izbrisati jezik s kodom :code jer je povezan s kanalom.',
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
            'title'   => 'Valute',
            'filters' => [
                'status' => 'Status',
                'enable' => 'Omogući',
                'all'    => 'Sve',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status mora biti 0 ili 1 (ili prazno za zadano omogućeno).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Uloge',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Korisnici',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktivno',
                'all'    => 'Sve',
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
        'export-too-large' => 'Ovaj izvoz je prevelik za pokretanje: procijenjeno :rows redaka × :columns stupaca (~:estimated) premašuje dostupan prostor (~:available). Suzite izvoz odabirom manje kanala/jezika (i atributa) i pokušajte ponovno.',
        'fields'           => [
            'file-format'         => 'Format datoteke',
            'with-media'          => 'S medijima',
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
            'file-path'      => 'Putanja Datoteke',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Omogućeno',
            'all'            => 'Sve',
        ],
        'products' => [
            'title'              => 'Proizvodi',
            'invalid-locales'    => 'Nisu sve odabrane lokalizacije dostupne za odabrane kanale.',
            'invalid-currencies' => 'Nisu sve odabrane valute dostupne za odabrane kanale.',
            'filters'            => [
                'channels'             => 'Kanali',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Valute',
                'currencies-info'      => 'Atributi cijena izvoze se po odabranoj valuti. Ostavite prazno za izvoz svih valuta kanala.',
                'locales'              => 'Lokalizacije',
                'locales-info'         => 'Lokalizirani atributi izvoze se jednom po odabranoj lokalizaciji. Ostavite prazno za izvoz svih lokalizacija kanala.',
                'attributes'           => 'Atributi',
                'attributes-info'      => 'Izvoze se samo odabrani atributi. Ostavite prazno za izvoz svih atributa obitelji.',
                'attribute-families'   => 'Obitelji atributa',
                'categories'           => 'Kategorije',
                'completeness'         => 'Potpunost',
                'completeness-options' => [
                    'none'         => 'Bez uvjeta potpunosti',
                    'at-least-one' => 'Potpun u barem jednoj odabranoj lokalizaciji',
                    'all'          => 'Potpun u svim odabranim lokalizacijama',
                ],
                'time-condition' => 'Vremenski uvjet',
                'time-options'   => [
                    'none'              => 'Bez uvjeta datuma',
                    'last-n-days'       => 'Proizvodi ažurirani u posljednjih N dana',
                    'between-dates'     => 'Proizvodi ažurirani između dva datuma',
                    'since-last-export' => 'Proizvodi ažurirani od posljednjeg izvoza',
                ],
                'time-value'     => 'Broj dana',
                'time-date'      => 'Datum početka',
                'time-date-end'  => 'Datum završetka',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Omogućeno',
                    'disable' => 'Onemogućeno',
                    'all'     => 'Sve',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identifikatori',
                'identifiers-info' => 'Zalijepite jedan SKU / identifikator po retku za izvoz samo tih proizvoda. Ostavite prazno za izvoz svih proizvoda.',
            ],
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
        'locales' => [
            'title' => 'Jezici',
        ],
        'channels' => [
            'title' => 'Kanali',
        ],
        'currencies' => [
            'title' => 'Valute',
        ],
        'roles' => [
            'title' => 'Uloge',
        ],
        'users' => [
            'title'   => 'Korisnici',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktivno',
                'all'    => 'Sve',
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
            'file-empty'           => 'Datoteka je prazna ili ne sadrži redak zaglavlja. Molimo učitajte valjanu datoteku s podacima.',
        ],
    ],
    'job' => [
        'started'   => 'Izvršenje posla je započelo',
        'completed' => 'Izvršenje posla je završeno',
    ],
];
