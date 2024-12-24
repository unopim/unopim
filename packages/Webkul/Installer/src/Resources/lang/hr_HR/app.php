<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Zadano',
            ],

            'attribute-groups'   => [
                'description'       => 'Opis',
                'general'           => 'Općenito',
                'inventories'       => 'Inventar',
                'meta-description'  => 'Meta Opis',
                'price'             => 'Cijena',
                'technical'         => 'Tehnički',
                'shipping'          => 'Dostava',
            ],

            'attributes'         => [
                'brand'                => 'Brend',
                'color'                => 'Boja',
                'cost'                 => 'Trošak',
                'description'          => 'Opis',
                'featured'             => 'Istaknuto',
                'guest-checkout'       => 'Kupovina bez računa',
                'height'               => 'Visina',
                'length'               => 'Duljina',
                'manage-stock'         => 'Upravljanje zalihama',
                'meta-description'     => 'Meta Opis',
                'meta-keywords'        => 'Meta Ključne Riječi',
                'meta-title'           => 'Meta Naslov',
                'name'                 => 'Ime',
                'new'                  => 'Novo',
                'price'                => 'Cijena',
                'product-number'       => 'Broj Proizvoda',
                'short-description'    => 'Kratki Opis',
                'size'                 => 'Veličina',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Posebna Cijena Od',
                'special-price-to'     => 'Posebna Cijena Do',
                'special-price'        => 'Posebna Cijena',
                'status'               => 'Status',
                'tax-category'         => 'Kategorija Poreza',
                'url-key'              => 'URL Ključ',
                'visible-individually' => 'Vidljivo Pojedinačno',
                'weight'               => 'Težina',
                'width'                => 'Širina',
            ],

            'attribute-options'  => [
                'black'  => 'Crna',
                'green'  => 'Zelena',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Crvena',
                's'      => 'S',
                'white'  => 'Bijela',
                'xl'     => 'XL',
                'yellow' => 'Žuta',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'Opis glavne kategorije',
                'name'        => 'Glavna',
            ],

            'category_fields' => [
                'name'        => 'Ime',
                'description' => 'Opis',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'Sadržaj stranice O Nama',
                    'title'   => 'O Nama',
                ],

                'contact-us'       => [
                    'content' => 'Sadržaj stranice Kontaktirajte Nas',
                    'title'   => 'Kontaktirajte Nas',
                ],

                'customer-service' => [
                    'content' => 'Sadržaj stranice Korisnička Podrška',
                    'title'   => 'Korisnička Podrška',
                ],

                'payment-policy'   => [
                    'content' => 'Sadržaj stranice Pravila Plaćanja',
                    'title'   => 'Pravila Plaćanja',
                ],

                'privacy-policy'   => [
                    'content' => 'Sadržaj stranice Pravila Privatnosti',
                    'title'   => 'Pravila Privatnosti',
                ],

                'refund-policy'    => [
                    'content' => 'Sadržaj stranice Pravila Povrata Novca',
                    'title'   => 'Pravila Povrata Novca',
                ],

                'return-policy'    => [
                    'content' => 'Sadržaj stranice Pravila Povrata',
                    'title'   => 'Pravila Povrata',
                ],

                'shipping-policy'  => [
                    'content' => 'Sadržaj stranice Pravila Dostave',
                    'title'   => 'Pravila Dostave',
                ],

                'terms-conditions' => [
                    'content' => 'Sadržaj stranice Uvjeti i Odredbe',
                    'title'   => 'Uvjeti i Odredbe',
                ],

                'terms-of-use'     => [
                    'content' => 'Sadržaj stranice Uvjeti Korištenja',
                    'title'   => 'Uvjeti Korištenja',
                ],

                'whats-new'        => [
                    'content' => 'Sadržaj stranice Što je Novo',
                    'title'   => 'Što je Novo',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'Demo Trgovina',
                'meta-keywords'    => 'Demo Trgovina Ključne Riječi',
                'meta-description' => 'Demo Trgovina Meta Opis',
                'name'             => 'Zadano',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Afganistanski Afghani',
                'CNY' => 'Kineski Yuan',
                'EUR' => 'Euro',
                'GBP' => 'Funta Sterling',
                'INR' => 'Indijska Rupija',
                'IRR' => 'Iranski Rial',
                'JPY' => 'Japanski Jen',
                'RUB' => 'Ruska Rublja',
                'SAR' => 'Saudijski Riyal',
                'TRY' => 'Turska Lira',
                'UAH' => 'Ukrajinska Hryvnia',
                'USD' => 'Američki Dolar',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => 'Općenito',
                'guest'     => 'Gost',
                'wholesale' => 'Veleprodaja',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Zadano',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'Svi Proizvodi',

                    'options' => [
                        'title' => 'Svi Proizvodi',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'Pogledaj Sve',
                        'description' => 'Predstavljamo naše nove Smjele Kolekcije! Istaknite se odvažnim dizajnom i živim bojama.',
                        'title'       => 'Spremni za naše nove Smjele Kolekcije!',
                    ],

                    'name'    => 'Smjele Kolekcije',
                ],

                'categories-collections' => [
                    'name' => 'Kolekcije Kategorija',
                ],

                'featured-collections'   => [
                    'name'    => 'Istaknute Kolekcije',

                    'options' => [
                        'title' => 'Istaknuti Proizvodi',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'Linkovi u Podnožju',

                    'options' => [
                        'about-us'         => 'O Nama',
                        'contact-us'       => 'Kontaktirajte Nas',
                        'customer-service' => 'Korisnička Podrška',
                        'payment-policy'   => 'Pravila Plaćanja',
                        'privacy-policy'   => 'Pravila Privatnosti',
                        'refund-policy'    => 'Pravila Povrata Novca',
                        'return-policy'    => 'Pravila Povrata',
                        'shipping-policy'  => 'Pravila Dostave',
                        'terms-conditions' => 'Uvjeti i Odredbe',
                        'terms-of-use'     => 'Uvjeti Korištenja',
                        'whats-new'        => 'Što je Novo',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Naše Kolekcije',
                        'sub-title-2' => 'Naše Kolekcije',
                        'title'       => 'Igrajte se s našim novim dodacima!',
                    ],

                    'name'    => 'Game Container',
                ],

                'image-carousel'         => [
                    'name'    => 'Karusel Slika',

                    'sliders' => [
                        'title' => 'Pripremite se za novu kolekciju',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'Novi Proizvodi',

                    'options' => [
                        'title' => 'Novi Proizvodi',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'Ostvarite DO 40% POPUSTA na prvu narudžbu KUPITE SADA',
                    ],

                    'name' => 'Informacije o Ponudi',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'Bez kamatne rate dostupne za sve glavne kreditne kartice',
                        'free-shipping-info'   => 'Uživajte u besplatnoj dostavi na sve narudžbe',
                        'product-replace-info' => 'Jednostavna zamjena proizvoda dostupna!',
                        'time-support-info'    => 'Posvećena 24/7 podrška putem chata i e-maila',
                    ],

                    'name'        => 'Sadržaj Usluga',

                    'title'       => [
                        'emi-available'   => 'EMI Dostupan',
                        'free-shipping'   => 'Besplatna Dostava',
                        'product-replace' => 'Zamjena Proizvoda',
                        'time-support'    => '24/7 Podrška',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Naše Kolekcije',
                        'sub-title-2' => 'Naše Kolekcije',
                        'sub-title-3' => 'Naše Kolekcije',
                        'sub-title-4' => 'Naše Kolekcije',
                        'sub-title-5' => 'Naše Kolekcije',
                        'sub-title-6' => 'Naše Kolekcije',
                        'title'       => 'Igrajte se s našim novim dodacima!',
                    ],

                    'name'    => 'Najbolje Kolekcije',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'Ova uloga omogućuje korisnicima potpuni pristup',
                'name'        => 'Administrator',
            ],

            'users' => [
                'name' => 'Primjer',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Administrator',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Potvrdite Lozinku',
                'email-address'    => 'admin@example.com',
                'email'            => 'E-mail',
                'password'         => 'Lozinka',
                'title'            => 'Kreiraj Administratora',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Dopuštene Valute',
                'allowed-locales'     => 'Dopušteni Lokaliteti',
                'application-name'    => 'Ime Aplikacije',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Kineski Yuan (CNY)',
                'database-connection' => 'Veza na Bazu Podataka',
                'database-hostname'   => 'Naziv Hostinga Baze',
                'database-name'       => 'Naziv Baze Podataka',
                'database-password'   => 'Lozinka Baze Podataka',
                'database-port'       => 'Port Baze Podataka',
                'database-prefix'     => 'Prefiks Baze Podataka',
                'database-username'   => 'Korisničko Ime Baze',
                'default-currency'    => 'Zadana Valuta',
                'default-locale'      => 'Zadani Lokalitet',
                'default-timezone'    => 'Zadana Vremenska Zona',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Zadani URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iranski Rial (IRR)',
                'israeli'             => 'Izraelski Šekel (ILS)',
                'japanese-yen'        => 'Japanski Jen (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Funta Sterlinga (GBP)',
                'rupee'               => 'Indijska Rupija (INR)',
                'russian-ruble'       => 'Ruska Rublja (RUB)',
                'saudi'               => 'Saudijski Rial (SAR)',
                'select-timezone'     => 'Odaberite Vremensku Zon',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Konfiguracija Baze Podataka',
                'turkish-lira'        => 'Turska Lira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrajinska Grivnja (UAH)',
                'usd'                 => 'Američki Dolar (USD)',
                'warning-message'     => 'Upozorenje! Zadane jezične i valutne postavke ne mogu se kasnije mijenjati.',
            ],

            'installation-processing' => [
                'unopim'            => 'Instalacija UnoPim-a',
                'unopim-info'       => 'Kreiranje tablica baze podataka, ovo može potrajati.',
                'title'             => 'Instalacija',
            ],

            'installation-completed' => [
                'admin-panel'                   => 'Administratorska Ploča',
                'unopim-forums'                 => 'UnoPim Forumi',
                'explore-unopim-extensions'     => 'Istraži UnoPim Proširenja',
                'title-info'                    => 'UnoPim je uspješno instaliran.',
                'title'                         => 'Instalacija Dovršena',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Kreiraj Tablice Baze',
                'install-info-button'     => 'Kliknite gumb ispod za početak',
                'install-info'            => 'UnoPim za instalaciju',
                'install'                 => 'Instalacija',
                'populate-database-table' => 'Popuni Tablice Baze',
                'start-installation'      => 'Započni Instalaciju',
                'title'                   => 'Spremno za Instalaciju',
            ],

            'start' => [
                'locale'        => 'Lokalitet',
                'main'          => 'Početak',
                'select-locale' => 'Odaberite Lokalitet',
                'title'         => 'Vaša UnoPim Instalacija',
                'welcome-title' => 'Dobrodošli u UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Kalendar',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Podaci o Datoteci',
                'filter'      => 'Filter',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Međunarodno',
                'json'        => 'JSON',
                'mbstring'    => 'MB Niz',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 ili noviji',
                'php'         => 'PHP',
                'session'     => 'Sesija',
                'title'       => 'Zahtjevi Sustava',
                'tokenizer'   => 'Tokenizator',
                'xml'         => 'XML',
            ],

            'arabic'                    => 'Arapski',
            'back'                      => 'Natrag',
            'unopim-info'               => 'Projekt Zajednice',
            'unopim-logo'               => 'UnoPim Logo',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'Bengalski',
            'chinese'                   => 'Kineski',
            'continue'                  => 'Nastavi',
            'dutch'                     => 'Nizozemski',
            'english'                   => 'Engleski',
            'french'                    => 'Francuski',
            'german'                    => 'Njemački',
            'hebrew'                    => 'Hebrejski',
            'hindi'                     => 'Hindski',
            'installation-description'  => 'UnoPim instalacija obično uključuje nekoliko koraka. Evo sažetka:',
            'wizard-language'           => 'Jezik Čarobnjaka za Instalaciju',
            'installation-info'         => 'Drago nam je što ste ovdje!',
            'installation-title'        => 'Dobrodošli u Instalaciju',
            'italian'                   => 'Talijanski',
            'japanese'                  => 'Japanski',
            'persian'                   => 'Perzijski',
            'polish'                    => 'Poljski',
            'portuguese'                => 'Brazilski Portugalski',
            'russian'                   => 'Ruski',
            'save-configuration'        => 'Spremi Konfiguraciju',
            'sinhala'                   => 'Sinhala',
            'skip'                      => 'Preskoči',
            'spanish'                   => 'Španjolski',
            'title'                     => 'UnoPim Instalacijski Program',
            'turkish'                   => 'Turski',
            'ukrainian'                 => 'Ukrajinski',
            'webkul'                    => 'Webkul',
        ],
    ],
];
