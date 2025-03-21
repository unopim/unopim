<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => 'Standard',
            'attribute-groups'   => [
                'description'      => 'Beskrivelse',
                'general'          => 'Generell',
                'inventories'      => 'Lager',
                'meta-description' => 'Meta-beskrivelse',
                'price'            => 'Pris',
                'technical'        => 'Teknisk',
                'shipping'         => 'Frakt',
            ],
            'attributes' => [
                'brand'                => 'Merke',
                'color'                => 'Farge',
                'cost'                 => 'Kostnad',
                'description'          => 'Beskrivelse',
                'featured'             => 'Vist',
                'guest-checkout'       => 'Gjestekasse',
                'height'               => 'Høyde',
                'length'               => 'Lengde',
                'manage-stock'         => 'Forvalte Lager',
                'meta-description'     => 'Meta-beskrivelse',
                'meta-keywords'        => 'Meta-ord',
                'meta-title'           => 'Meta-tittel',
                'name'                 => 'Navn',
                'new'                  => 'Ny',
                'price'                => 'Pris',
                'product-number'       => 'Produktnummer',
                'short-description'    => 'Kort beskrivelse',
                'size'                 => 'Størrelse',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Spesialpris Fra',
                'special-price-to'     => 'Spesialpris Til',
                'special-price'        => 'Spesialpris',
                'status'               => 'Status',
                'tax-category'         => 'Avgiftskategori',
                'url-key'              => 'URL-nøkkel',
                'visible-individually' => 'Synlig Enkeltvis',
                'weight'               => 'Vekt',
                'width'                => 'Bredde',
            ],
            'attribute-options' => [
                'black'  => 'Svart',
                'green'  => 'Grønn',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Rød',
                's'      => 'S',
                'white'  => 'Hvit',
                'xl'     => 'XL',
                'yellow' => 'Gul',
            ],
        ],
        'category' => [
            'categories' => [
                'description' => 'Hovedkategori beskrivelse',
                'name'        => 'Hovedkategori',
            ],
            'category_fields' => [
                'name'        => 'Navn',
                'description' => 'Beskrivelse',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Om oss-sideinnhold',
                    'title'   => 'Om oss',
                ],
                'contact-us' => [
                    'content' => 'Kontakt oss-sideinnhold',
                    'title'   => 'Kontakt oss',
                ],
                'customer-service' => [
                    'content' => 'Kundeservice-sideinnhold',
                    'title'   => 'Kundeservice',
                ],
                'payment-policy' => [
                    'content' => 'Betalingspolicy-sideinnhold',
                    'title'   => 'Betalingspolicy',
                ],
                'privacy-policy' => [
                    'content' => 'Personvernerklæring-sideinnhold',
                    'title'   => 'Personvernerklæring',
                ],
                'refund-policy' => [
                    'content' => 'Refusjonspolitikk-sideinnhold',
                    'title'   => 'Refusjonspolitikk',
                ],
                'return-policy' => [
                    'content' => 'Returpolitikk-sideinnhold',
                    'title'   => 'Returpolitikk',
                ],
                'shipping-policy' => [
                    'content' => 'Fraktpolicy-sideinnhold',
                    'title'   => 'Fraktpolicy',
                ],
                'terms-conditions' => [
                    'content' => 'Vilkår og betingelser-sideinnhold',
                    'title'   => 'Vilkår og betingelser',
                ],
                'terms-of-use' => [
                    'content' => 'Vilkår for bruk-sideinnhold',
                    'title'   => 'Vilkår for bruk',
                ],
                'whats-new' => [
                    'content' => 'Hva er nytt sideinnhold',
                    'title'   => 'Hva er nytt',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'meta-title'       => 'Demo butikk',
                'meta-keywords'    => 'Demo butikk meta ord',
                'meta-description' => 'Demo butikk meta beskrivelse',
                'name'             => 'Standard',
            ],
            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israelisk Shekel',
                'CNY' => 'Kinesisk Yuan',
                'EUR' => 'EURO',
                'GBP' => 'Pund Sterling',
                'INR' => 'Indisk Rupee',
                'IRR' => 'Iransk Rial',
                'JPY' => 'Japansk Yen',
                'RUB' => 'Russisk Rubel',
                'SAR' => 'Saudi Riyal',
                'TRY' => 'Tyrkisk Lira',
                'UAH' => 'Ukrainsk Hryvnia',
                'USD' => 'US Dollar',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general'   => 'Generell',
                'guest'     => 'Gjest',
                'wholesale' => 'Engros',
            ],
        ],
        'inventory' => [
            'inventory-sources' => [
                'name' => 'Standard',
            ],
        ],
        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name'    => 'Alle produkter',
                    'options' => [
                        'title' => 'Alle produkter',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Vis alle',
                        'description' => 'Presenting the new Bold collection! Step up your style game with daring designs and vibrant colors. Define your outfit in a whole new way with bold patterns and statement hues. Get ready for special moments with our bold collection!',
                        'title'       => 'Meet the new Bold collection!',
                    ],
                    'name' => 'Bold Collection',
                ],
                'categories-collections' => [
                    'name' => 'Categories Collection',
                ],
                'featured-collections' => [
                    'name'    => 'Featured Collection',
                    'options' => [
                        'title' => 'Featured Products',
                    ],
                ],
                'footer-links' => [
                    'name'    => 'Footer Links',
                    'options' => [
                        'about-us'         => 'About Us',
                        'contact-us'       => 'Contact Us',
                        'customer-service' => 'Customer Service',
                        'payment-policy'   => 'Payment Policy',
                        'privacy-policy'   => 'Privacy Policy',
                        'refund-policy'    => 'Refund Policy',
                        'return-policy'    => 'Return Policy',
                        'shipping-policy'  => 'Shipping Policy',
                        'terms-conditions' => 'Terms & Conditions',
                        'terms-of-use'     => 'Terms of Use',
                        'whats-new'        => 'What\'s New',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Our Collections',
                        'sub-title-2' => 'Our Collections',
                        'title'       => 'Prepare for a game with our new additions!',
                    ],
                    'name' => 'Game Container',
                ],
                'image-carousel' => [
                    'name'    => 'Image Carousel',
                    'sliders' => [
                        'title' => 'Get ready to meet the new collection',
                    ],
                ],
                'new-products' => [
                    'name'    => 'New Products',
                    'options' => [
                        'title' => 'New Products',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => 'Up to 40% off on first orders! SHOP NOW',
                    ],
                    'name' => 'Offer Information',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'Available EMI on all major credit cards',
                        'free-shipping-info'   => 'Enjoy free shipping on all orders',
                        'product-replace-info' => 'Easy replacement available!',
                        'time-support-info'    => 'Dedicated 24/7 support via chat and email',
                    ],
                    'name'  => 'Services Content',
                    'title' => [
                        'emi-available'   => 'EMI Available',
                        'free-shipping'   => 'Free Shipping',
                        'product-replace' => 'Product Replacement',
                        'time-support'    => '24/7 Support',
                    ],
                ],
                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Our Collections',
                        'sub-title-2' => 'Our Collections',
                        'sub-title-3' => 'Our Collections',
                        'sub-title-4' => 'Our Collections',
                        'sub-title-5' => 'Our Collections',
                        'sub-title-6' => 'Our Collections',
                        'title'       => 'Prepare for a game with our new additions!',
                    ],
                    'name' => 'Top Collections',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => 'This role will have all access',
                'name'        => 'Administrator',
            ],
            'users' => [
                'name' => 'Example',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Administrator',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Bekreft passord',
                'email-address'    => 'admin@example.com',
                'email'            => 'E-post',
                'password'         => 'Passord',
                'title'            => 'Opprett Administrator',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Tillatte valutaer',
                'allowed-locales'     => 'Tillatte lokale innstillinger',
                'application-name'    => 'Applikasjonsnavn',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Kinesisk Yuan (CNY)',
                'database-connection' => 'Databaseforbindelse',
                'database-hostname'   => 'Databasevertsnavn',
                'database-name'       => 'Databasenavn',
                'database-password'   => 'Databasepassord',
                'database-port'       => 'Databaseport',
                'database-prefix'     => 'Databaseprefiks',
                'database-username'   => 'Databasenavnbruker',
                'default-currency'    => 'Standard valuta',
                'default-locale'      => 'Standard lokalitet',
                'default-timezone'    => 'Standard tidssone',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Standard URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iransk Rial (IRR)',
                'israeli'             => 'Israelsk Shekel (ILS)',
                'japanese-yen'        => 'Japansk Yen (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Britisk Pund (GBP)',
                'rupee'               => 'Indisk Rupi (INR)',
                'russian-ruble'       => 'Russisk Rubel (RUB)',
                'saudi'               => 'Saudi Riyal (SAR)',
                'select-timezone'     => 'Velg tidssone',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Databasen Konfigurasjon',
                'turkish-lira'        => 'Tyrkisk Lira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrainsk Hryvnia (UAH)',
                'usd'                 => 'Amerikansk Dollar (USD)',
                'warning-message'     => 'Advarsel! Standard lokalitet og valuta kan ikke endres senere.',
            ],

            'installation-processing' => [
                'unopim'      => 'Installerer UnoPim',
                'unopim-info' => 'Oppretter databasetabeller, dette kan ta litt tid',
                'title'       => 'Installerer',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Administratorpanel',
                'unopim-forums'             => 'UnoPim-forum',
                'explore-unopim-extensions' => 'Utforsk UnoPim-utvidelser',
                'title-info'                => 'UnoPim ble installert vellykket.',
                'title'                     => 'Installasjonen fullført',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Opprett databasen tabeller',
                'install-info-button'     => 'Klikk på knappen nedenfor for å starte',
                'install-info'            => 'å installere UnoPim',
                'install'                 => 'Installer',
                'populate-database-table' => 'Populer databasen tabeller',
                'start-installation'      => 'Start installasjonen',
                'title'                   => 'Klar for installasjon',
            ],

            'start' => [
                'locale'        => 'Lokalisering',
                'main'          => 'Start',
                'select-locale' => 'Velg lokalisering',
                'title'         => 'Installer UnoPim',
                'welcome-title' => 'Velkommen til UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Kalender',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Filinfo',
                'filter'      => 'Filter',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Internasjonal',
                'json'        => 'JSON',
                'mbstring'    => 'MBString',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 eller nyere',
                'php'         => 'PHP',
                'session'     => 'Session',
                'title'       => 'Systemkrav',
                'tokenizer'   => 'Tokenizer',
                'xml'         => 'XML',
            ],

            'back'                     => 'Tilbake',
            'unopim-info'              => 'Fellesskapsprosjekt',
            'unopim-logo'              => 'UnoPim-logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Fortsett',
            'installation-description' => 'Installasjonen av UnoPim består av flere trinn. Her er en oversikt:',
            'wizard-language'          => 'Språk for installasjonsveiviser',
            'installation-info'        => 'Takk for at du er her!',
            'installation-title'       => 'Velkommen til installasjonen',
            'save-configuration'       => 'Lagre konfigurasjon',
            'skip'                     => 'Hopp over',
            'title'                    => 'UnoPim Installasjonsveiviser',
            'webkul'                   => 'Webkul',
        ],
    ],
];
