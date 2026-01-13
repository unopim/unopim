<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Zadano',
            ],

            'attribute-groups' => [
                'description'      => 'Opis',
                'general'          => 'Općenito',
                'meta-description' => 'Meta opis',
                'price'            => 'Cijena',
                'media'            => 'Mediji',
            ],

            'attributes' => [
                'brand'                => 'Brend',
                'color'                => 'Boja',
                'cost'                 => 'Trošak',
                'description'          => 'Opis',
                'featured'             => 'Istaknuto',
                'guest-checkout'       => 'Kupovina bez računa',
                'height'               => 'Visina',
                'image'                => 'Slika',
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
                'tax-category'         => 'Kategorija Poreza',
                'url-key'              => 'URL Ključ',
                'visible-individually' => 'Vidljivo Pojedinačno',
                'weight'               => 'Težina',
                'width'                => 'Širina',
            ],

            'attribute-options' => [
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

        'category' => [
            'categories' => [
                'description' => 'Opis glavne kategorije',
                'name'        => 'Glavna',
            ],

            'category_fields' => [
                'name'        => 'Ime',
                'description' => 'Opis',
            ],
        ],

        'core' => [
            'channels' => [
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

        'user' => [
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
        'middleware' => [
            'already-installed' => 'Aplikacija je već instalirana.',
        ],

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
                'unopim'      => 'Instalacija UnoPim-a',
                'unopim-info' => 'Kreiranje tablica baze podataka, ovo može potrajati.',
                'title'       => 'Instalacija',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Administratorska Ploča',
                'unopim-forums'             => 'UnoPim Forumi',
                'explore-unopim-extensions' => 'Istraži UnoPim Proširenja',
                'title-info'                => 'UnoPim je uspješno instaliran.',
                'title'                     => 'Instalacija Dovršena',
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

            'back'                     => 'Natrag',
            'unopim-info'              => 'Projekt Zajednice',
            'unopim-logo'              => 'UnoPim Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Nastavi',
            'installation-description' => 'UnoPim instalacija obično uključuje nekoliko koraka. Evo sažetka:',
            'wizard-language'          => 'Jezik Čarobnjaka za Instalaciju',
            'installation-info'        => 'Drago nam je što ste ovdje!',
            'installation-title'       => 'Dobrodošli u Instalaciju',
            'save-configuration'       => 'Spremi Konfiguraciju',
            'skip'                     => 'Preskoči',
            'title'                    => 'UnoPim Instalacijski Program',
            'webkul'                   => 'Webkul',
        ],
    ],
];
