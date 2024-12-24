<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Standard',
            ],

            'attribute-groups'   => [
                'description'       => 'Beskrivelse',
                'general'           => 'Generel',
                'inventories'       => 'Lager',
                'meta-description'  => 'Meta Beskrivelse',
                'price'             => 'Pris',
                'technical'         => 'Teknisk',
                'shipping'          => 'Fragt',
            ],

            'attributes'         => [
                'brand'                => 'Mærke',
                'color'                => 'Farve',
                'cost'                 => 'Omkostning',
                'description'          => 'Beskrivelse',
                'featured'             => 'Fremhævet',
                'guest-checkout'       => 'Gæstekøb',
                'height'               => 'Højde',
                'length'               => 'Længde',
                'manage-stock'         => 'Styr Lager',
                'meta-description'     => 'Meta Beskrivelse',
                'meta-keywords'        => 'Meta Nøgler',
                'meta-title'           => 'Meta Titel',
                'name'                 => 'Navn',
                'new'                  => 'Ny',
                'price'                => 'Pris',
                'product-number'       => 'Produkt Nummer',
                'short-description'    => 'Kort Beskrivelse',
                'size'                 => 'Størrelse',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Særlig Pris Fra',
                'special-price-to'     => 'Særlig Pris Til',
                'special-price'        => 'Særlig Pris',
                'status'               => 'Status',
                'tax-category'         => 'Moms Kategori',
                'url-key'              => 'URL Nøgle',
                'visible-individually' => 'Synlig Individuelt',
                'weight'               => 'Vægt',
                'width'                => 'Bredde',
            ],

            'attribute-options'  => [
                'black'  => 'Sort',
                'green'  => 'Grøn',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Rød',
                's'      => 'S',
                'white'  => 'Hvid',
                'xl'     => 'XL',
                'yellow' => 'Gul',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'Rod Kategori Beskrivelse',
                'name'        => 'Rod',
            ],

            'category_fields' => [
                'name'        => 'Navn',
                'description' => 'Beskrivelse',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'Om Os Side Indhold',
                    'title'   => 'Om Os',
                ],

                'contact-us'       => [
                    'content' => 'Kontakt Os Side Indhold',
                    'title'   => 'Kontakt Os',
                ],

                'customer-service' => [
                    'content' => 'Kunde Service Side Indhold',
                    'title'   => 'Kunde Service',
                ],

                'payment-policy'   => [
                    'content' => 'Betalingspolitik Side Indhold',
                    'title'   => 'Betalingspolitik',
                ],

                'privacy-policy'   => [
                    'content' => 'Fortrolighedspolitik Side Indhold',
                    'title'   => 'Fortrolighedspolitik',
                ],

                'refund-policy'    => [
                    'content' => 'Refusion Politik Side Indhold',
                    'title'   => 'Refusion Politik',
                ],

                'return-policy'    => [
                    'content' => 'Returneringspolitik Side Indhold',
                    'title'   => 'Returneringspolitik',
                ],

                'shipping-policy'  => [
                    'content' => 'Fragtpolitik Side Indhold',
                    'title'   => 'Fragtpolitik',
                ],

                'terms-conditions' => [
                    'content' => 'Vilkår & Betingelser Side Indhold',
                    'title'   => 'Vilkår & Betingelser',
                ],

                'terms-of-use'     => [
                    'content' => 'Brugsbetingelser Side Indhold',
                    'title'   => 'Brugsbetingelser',
                ],

                'whats-new'        => [
                    'content' => 'Hvad er Nyt side indhold',
                    'title'   => 'Hvad er Nyt',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'Demo butik',
                'meta-keywords'    => 'Demo butik meta nøgleord',
                'meta-description' => 'Demo butik meta beskrivelse',
                'name'             => 'Standard',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israelsk Shekel',
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

        'customer'  => [
            'customer-groups' => [
                'general'   => 'Generel',
                'guest'     => 'Gæst',
                'wholesale' => 'Engros',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Standard',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'Alle Produkter',

                    'options' => [
                        'title' => 'Alle Produkter',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'Se Alle',
                        'description' => 'Præsenterer Vores Nye Dristige Samlinger! Forhæv din stil med dristige designs og livlige udtryk. Udforsk markante mønstre og dristige farver, der gendefinerer din garderobe. Gør dig klar til at omfavne det ekstraordinære!',
                        'title'       => 'Gør dig klar til vores nye Dristige Samlinger!',
                    ],

                    'name'    => 'Dristige Samlinger',
                ],

                'categories-collections' => [
                    'name' => 'Kategorier Samlinger',
                ],

                'featured-collections'   => [
                    'name'    => 'Fremhævede Samlinger',

                    'options' => [
                        'title' => 'Fremhævede Produkter',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'Footer Links',

                    'options' => [
                        'about-us'         => 'Om Os',
                        'contact-us'       => 'Kontakt Os',
                        'customer-service' => 'Kunde Service',
                        'payment-policy'   => 'Betalingspolitik',
                        'privacy-policy'   => 'Fortrolighedspolitik',
                        'refund-policy'    => 'Refusion Politik',
                        'return-policy'    => 'Returneringspolitik',
                        'shipping-policy'  => 'Fragtpolitik',
                        'terms-conditions' => 'Vilkår & Betingelser',
                        'terms-of-use'     => 'Brugsbetingelser',
                        'whats-new'        => 'Hvad er Nyt',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Vores Samlinger',
                        'sub-title-2' => 'Vores Samlinger',
                        'title'       => 'Spillet med vores nye tilføjelser!',
                    ],

                    'name'    => 'Spil Container',
                ],

                'image-carousel'         => [
                    'name'    => 'Billed Carousel',

                    'sliders' => [
                        'title' => 'Gør dig klar til Nye Samlinger',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'Nye Produkter',

                    'options' => [
                        'title' => 'Nye Produkter',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'Få OP TIL 40% RABAT på din 1. ordre HANDL NU',
                    ],

                    'name' => 'Tilbud Information',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'Ingen omkostninger EMI tilgængelig på alle større kreditkort',
                        'free-shipping-info'   => 'Gratis forsendelse på alle ordrer',
                        'product-replace-info' => 'Let produkt erstatning tilgængelig!',
                        'time-support-info'    => 'Dedikeret 24/7 support via chat og e-mail',
                    ],

                    'name'        => 'Tjenester Indhold',

                    'title'       => [
                        'emi-available'   => 'Emi Tilgængelig',
                        'free-shipping'   => 'Gratis Forsendelse',
                        'product-replace' => 'Produkt Erstatning',
                        'time-support'    => '24/7 Support',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Vores Samlinger',
                        'sub-title-2' => 'Vores Samlinger',
                        'sub-title-3' => 'Vores Samlinger',
                        'sub-title-4' => 'Vores Samlinger',
                        'sub-title-5' => 'Vores Samlinger',
                        'sub-title-6' => 'Vores Samlinger',
                        'title'       => 'Spillet med vores nye tilføjelser!',
                    ],

                    'name'    => 'Top Samlinger',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'Denne rollebrugere vil have alle adgang',
                'name'        => 'Administrator',
            ],

            'users' => [
                'name' => 'Eksempel',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Administrator',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Bekræft adgangskode',
                'email-address'    => 'admin@example.com',
                'email'            => 'E-mail',
                'password'         => 'Adgangskode',
                'title'            => 'Opret administrator',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Tilladte valutaer',
                'allowed-locales'     => 'Tilladte sprog',
                'application-name'    => 'Applikationsnavn',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Kinesisk yuan (CNY)',
                'database-connection' => 'Databaseforbindelse',
                'database-hostname'   => 'Databasevært',
                'database-name'       => 'Databasenavn',
                'database-password'   => 'Databaseadgangskode',
                'database-port'       => 'Databaseport',
                'database-prefix'     => 'Databaseprefix',
                'database-username'   => 'Databasebrugernavn',
                'default-currency'    => 'Standardvaluta',
                'default-locale'      => 'Standardsprog',
                'default-timezone'    => 'Standard tidszone',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Standard URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iransk rial (IRR)',
                'israeli'             => 'Israelsk shekel (ILS)',
                'japanese-yen'        => 'Japansk yen (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Britisk pund (GBP)',
                'rupee'               => 'Indisk rupee (INR)',
                'russian-ruble'       => 'Russisk rubel (RUB)',
                'saudi'               => 'Saudisk riyal (SAR)',
                'select-timezone'     => 'Vælg tidszone',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Databasekonfiguration',
                'turkish-lira'        => 'Tyrkisk lira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrainsk hryvnia (UAH)',
                'usd'                 => 'Amerikansk dollar (USD)',
                'warning-message'     => 'Advarsel! Indstillingerne for standardsprog og -valuta er permanente og kan ikke ændres igen.',
            ],

            'installation-processing' => [
                'unopim'            => 'UnoPim Installation',
                'unopim-info'       => 'Opretter databasetabeller, dette kan tage et øjeblik',
                'title'             => 'Installation',
            ],

            'installation-completed' => [
                'admin-panel'                   => 'Administratorpanel',
                'unopim-forums'                 => 'UnoPim Forum',
                'explore-unopim-extensions'     => 'Udforsk UnoPim-udvidelser',
                'title-info'                    => 'UnoPim er blevet installeret korrekt på dit system.',
                'title'                         => 'Installation fuldført',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Opret databasetabeller',
                'install-info-button'     => 'Klik på knappen nedenfor for at',
                'install-info'            => 'UnoPim til installation',
                'install'                 => 'Installation',
                'populate-database-table' => 'Udfyld databasetabeller',
                'start-installation'      => 'Start installation',
                'title'                   => 'Klar til installation',
            ],

            'start' => [
                'locale'        => 'Sprog',
                'main'          => 'Start',
                'select-locale' => 'Vælg sprog',
                'title'         => 'Din UnoPim installation',
                'welcome-title' => 'Velkommen til UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Kalender',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'FileInfo',
                'filter'      => 'Filter',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Intl',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
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

            'arabic'                    => 'Arabisk',
            'back'                      => 'Tilbage',
            'unopim-info'               => 'Et fællesskabsprojekt af',
            'unopim-logo'               => 'UnoPim Logo',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'Bengali',
            'chinese'                   => 'Kinesisk',
            'continue'                  => 'Fortsæt',
            'dutch'                     => 'Hollandsk',
            'english'                   => 'Engelsk',
            'french'                    => 'Fransk',
            'german'                    => 'Tysk',
            'hebrew'                    => 'Hebraisk',
            'hindi'                     => 'Hindi',
            'installation-description'  => 'UnoPim installationen omfatter flere trin. Her er et generelt overblik:',
            'wizard-language'           => 'Installationsguide sprog',
            'installation-info'         => 'Vi er glade for at se dig her!',
            'installation-title'        => 'Velkommen til installation',
            'italian'                   => 'Italiensk',
            'japanese'                  => 'Japansk',
            'persian'                   => 'Persisk',
            'polish'                    => 'Polsk',
            'portuguese'                => 'Brasiliansk portugisisk',
            'russian'                   => 'Russisk',
            'save-configuration'        => 'Gem konfiguration',
            'sinhala'                   => 'Singalesisk',
            'skip'                      => 'Spring over',
            'spanish'                   => 'Spansk',
            'title'                     => 'UnoPim Installationsguide',
            'turkish'                   => 'Tyrkisk',
            'ukrainian'                 => 'Ukrainsk',
            'webkul'                    => 'Webkul',
        ],
    ],
];
