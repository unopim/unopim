<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Standaard',
            ],

            'attribute-groups'   => [
                'description'       => 'Beschrijving',
                'general'           => 'Algemeen',
                'inventories'       => 'Voorraden',
                'meta-description'  => 'Metabeschrijving',
                'price'             => 'Prijs',
                'technical'         => 'Technisch',
                'shipping'          => 'Verzending',
            ],

            'attributes'         => [
                'brand'                => 'Merk',
                'color'                => 'Kleur',
                'cost'                 => 'Kosten',
                'description'          => 'Beschrijving',
                'featured'             => 'Uitgelicht',
                'guest-checkout'       => 'Afrekenen als gast',
                'height'               => 'Hoogte',
                'length'               => 'Lengte',
                'manage-stock'         => 'Beheer voorraad',
                'meta-description'     => 'Metabeschrijving',
                'meta-keywords'        => 'Meta-trefwoorden',
                'meta-title'           => 'Metatitel',
                'name'                 => 'Naam',
                'new'                  => 'Nieuw',
                'price'                => 'Prijs',
                'product-number'       => 'Productnummer',
                'short-description'    => 'Korte beschrijving',
                'size'                 => 'Maat',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Speciale prijs vanaf',
                'special-price-to'     => 'Speciale prijs voor',
                'special-price'        => 'Speciale prijs',
                'status'               => 'Status',
                'tax-category'         => 'Belastingcategorie',
                'url-key'              => 'URL-sleutel',
                'visible-individually' => 'Individueel zichtbaar',
                'weight'               => 'Gewicht',
                'width'                => 'Breedte',
            ],

            'attribute-options'  => [
                'black'  => 'Zwart',
                'green'  => 'Groente',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Rood',
                's'      => 'S',
                'white'  => 'Wit',
                'xl'     => 'XL',
                'yellow' => 'Geel',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'Beschrijving van hoofdcategorie',
                'name'        => 'Wortel',
            ],

            'category_fields' => [
                'name'        => 'Naam',
                'description' => 'Beschrijving',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'Over ons Pagina-inhoud',
                    'title'   => 'Over ons',
                ],

                'contact-us'       => [
                    'content' => 'Neem contact met ons op Pagina-inhoud',
                    'title'   => 'Neem contact met ons op',
                ],

                'customer-service' => [
                    'content' => 'Inhoud klantenservicepagina',
                    'title'   => 'Klantenservice',
                ],

                'payment-policy'   => [
                    'content' => 'Betalingsbeleid Pagina-inhoud',
                    'title'   => 'Betalingsbeleid',
                ],

                'privacy-policy'   => [
                    'content' => 'Pagina-inhoud privacybeleid',
                    'title'   => 'Privacybeleid',
                ],

                'refund-policy'    => [
                    'content' => 'Pagina-inhoud van het restitutiebeleid',
                    'title'   => 'Terugbetalingsbeleid',
                ],

                'return-policy'    => [
                    'content' => 'Inhoud van de pagina Retourbeleid',
                    'title'   => 'Retourbeleid',
                ],

                'shipping-policy'  => [
                    'content' => 'Verzendbeleid Pagina-inhoud',
                    'title'   => 'Verzendbeleid',
                ],

                'terms-conditions' => [
                    'content' => 'Algemene voorwaarden Pagina-inhoud',
                    'title'   => 'Algemene voorwaarden',
                ],

                'terms-of-use'     => [
                    'content' => 'Gebruiksvoorwaarden Pagina-inhoud',
                    'title'   => 'Gebruiksvoorwaarden',
                ],

                'whats-new'        => [
                    'content' => 'Wat is er nieuw pagina-inhoud',
                    'title'   => 'Wat is er nieuw',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'Demo winkel',
                'meta-keywords'    => 'Metazoekwoord voor demowinkel',
                'meta-description' => 'Metabeschrijving van de demowinkel',
                'name'             => 'Standaard',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israëlische sjekel',
                'CNY' => 'Chinese Yuan',
                'EUR' => 'EURO',
                'GBP' => 'Pond Sterling',
                'INR' => 'Indiase Roepie',
                'IRR' => 'Iraanse Rial',
                'JPY' => 'Japanse Yen',
                'RUB' => 'Russische roebel',
                'SAR' => 'Saoedische Riyal',
                'TRY' => 'Turkse Lira',
                'UAH' => 'Oekraïense hryvnia',
                'USD' => 'Amerikaanse dollar',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => 'Algemeen',
                'guest'     => 'Gast',
                'wholesale' => 'Groothandel',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Standaard',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'Alle producten',

                    'options' => [
                        'title' => 'Alle producten',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'Bekijk alles',
                        'description' => 'Maak kennis met onze nieuwe gewaagde collecties! Verhoog uw stijl met gedurfde ontwerpen en levendige uitspraken. Ontdek opvallende patronen en opvallende kleuren die je garderobe opnieuw definiëren. Maak je klaar om het buitengewone te omarmen!',
                        'title'       => 'Maak je klaar voor onze nieuwe Bold Collections!',
                    ],

                    'name'    => 'Gedurfde collecties',
                ],

                'categories-collections' => [
                    'name' => 'Categorieën Collecties',
                ],

                'featured-collections'   => [
                    'name'    => 'Uitgelichte collecties',

                    'options' => [
                        'title' => 'Uitgelichte producten',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'Voettekstlinks',

                    'options' => [
                        'about-us'         => 'Over ons',
                        'contact-us'       => 'Neem contact met ons op',
                        'customer-service' => 'Klantenservice',
                        'payment-policy'   => 'Betalingsbeleid',
                        'privacy-policy'   => 'Privacybeleid',
                        'refund-policy'    => 'Terugbetalingsbeleid',
                        'return-policy'    => 'Retourbeleid',
                        'shipping-policy'  => 'Verzendbeleid',
                        'terms-conditions' => 'Algemene voorwaarden',
                        'terms-of-use'     => 'Gebruiksvoorwaarden',
                        'whats-new'        => 'Wat is er nieuw',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Onze collecties',
                        'sub-title-2' => 'Onze collecties',
                        'title'       => 'Het spel met onze nieuwe toevoegingen!',
                    ],

                    'name'    => 'Spelcontainer',
                ],

                'image-carousel'         => [
                    'name'    => 'Afbeeldingscarrousel',

                    'sliders' => [
                        'title' => 'Maak je klaar voor de nieuwe collectie',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'Nieuwe producten',

                    'options' => [
                        'title' => 'Nieuwe producten',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'Ontvang TOT 40% KORTING op uw 1e bestelling SHOP NU',
                    ],

                    'name' => 'Informatie aanbieden',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'Gratis EMI beschikbaar op alle gangbare creditcards',
                        'free-shipping-info'   => 'Geniet van gratis verzending op alle bestellingen',
                        'product-replace-info' => 'Gemakkelijke productvervanging beschikbaar!',
                        'time-support-info'    => 'Toegewijde 24/7 ondersteuning via chat en e-mail',
                    ],

                    'name'        => 'Diensten Inhoud',

                    'title'       => [
                        'emi-available'   => 'Em beschikbaar',
                        'free-shipping'   => 'Gratis verzending',
                        'product-replace' => 'Product vervangen',
                        'time-support'    => '24/7 ondersteuning',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Onze collecties',
                        'sub-title-2' => 'Onze collecties',
                        'sub-title-3' => 'Onze collecties',
                        'sub-title-4' => 'Onze collecties',
                        'sub-title-5' => 'Onze collecties',
                        'sub-title-6' => 'Onze collecties',
                        'title'       => 'Het spel met onze nieuwe toevoegingen!',
                    ],

                    'name'    => 'Topcollecties',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'Gebruikers met deze rol hebben alle toegang',
                'name'        => 'Beheerder',
            ],

            'users' => [
                'name' => 'Voorbeeld',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Beheerder',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Bevestig wachtwoord',
                'email-address'    => 'beheerder@voorbeeld.com',
                'email'            => 'E-mail',
                'password'         => 'Wachtwoord',
                'title'            => 'Beheerder aanmaken',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Toegestane valuta\'s',
                'allowed-locales'     => 'Toegestane landinstellingen',
                'application-name'    => 'Applicatienaam',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Chinese yuan (CNY)',
                'database-connection' => 'Databaseverbinding',
                'database-hostname'   => 'Databasehostnaam',
                'database-name'       => 'Databasenaam',
                'database-password'   => 'Databasewachtwoord',
                'database-port'       => 'Databasepoort',
                'database-prefix'     => 'Databasevoorvoegsel',
                'database-username'   => 'Database-gebruikersnaam',
                'default-currency'    => 'Standaardvaluta',
                'default-locale'      => 'Standaardlandinstelling',
                'default-timezone'    => 'Standaard tijdzone',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Standaard-URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iraanse Rial (IRR)',
                'israeli'             => 'Israëlische sjekel (AFN)',
                'japanese-yen'        => 'Japanse Yen (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Pond Sterling (GBP)',
                'rupee'               => 'Indiase roepie (INR)',
                'russian-ruble'       => 'Russische roebel (RUB)',
                'saudi'               => 'Saoedische riyal (SAR)',
                'select-timezone'     => 'Selecteer Tijdzone',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Databaseconfiguratie',
                'turkish-lira'        => 'Turkse lira (TRY)',
                'ukrainian-hryvnia'   => 'Oekraïense hryvnia (UAH)',
                'usd'                 => 'Amerikaanse dollar (USD)',
                'warning-message'     => 'Pas op! De instellingen voor uw standaardsysteemtalen en de standaardvaluta zijn permanent en kunnen nooit meer worden gewijzigd.',
            ],

            'installation-processing'   => [
                'unopim'            => 'Installatie UnoPim',
                'unopim-info'       => 'Het maken van de databasetabellen kan even duren',
                'title'             => 'Installatie',
            ],

            'installation-completed'    => [
                'admin-panel'                   => 'Beheerderspaneel',
                'unopim-forums'                 => 'UnoPim-forum',
                'explore-unopim-extensions'     => 'Ontdek de UnoPim-extensie',
                'title-info'                    => 'UnoPim is succesvol op uw systeem geïnstalleerd.',
                'title'                         => 'Installatie voltooid',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => 'Maak de databasetabel',
                'install-info-button'     => 'Klik op onderstaande knop om',
                'install-info'            => 'UnoPim voor installatie',
                'install'                 => 'Installatie',
                'populate-database-table' => 'Vul de databasetabellen in',
                'start-installation'      => 'Begin met de installatie',
                'title'                   => 'Klaar voor installatie',
            ],

            'start'                     => [
                'locale'        => 'Lokaal',
                'main'          => 'Begin',
                'select-locale' => 'Selecteer Landinstelling',
                'title'         => 'Uw UnoPim-installatie',
                'welcome-title' => 'Welkom bij UnoPim '.core()->version(),
            ],

            'server-requirements'       => [
                'calendar'    => 'Kalender',
                'ctype'       => 'cType',
                'curl'        => 'krul',
                'dom'         => 'dom',
                'fileinfo'    => 'bestandsInfo',
                'filter'      => 'Filter',
                'gd'          => 'GD',
                'hash'        => 'Hasj',
                'intl'        => 'int',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'opensl',
                'pcre'        => 'pcre',
                'pdo'         => 'pdo',
                'php-version' => '8.2 of hoger',
                'php'         => 'PHP',
                'session'     => 'sessie',
                'title'       => 'Systeemvereisten',
                'tokenizer'   => 'tokenizer',
                'xml'         => 'XML',
            ],

            'arabic'                    => 'Arabisch',
            'back'                      => 'Rug',
            'UnoPim-info'               => 'een gemeenschapsproject van',
            'unopim-logo'               => 'UnoPim-logo',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'Bengaals',
            'chinese'                   => 'Chinese',
            'continue'                  => 'Doorgaan',
            'dutch'                     => 'Nederlands',
            'english'                   => 'Engels',
            'french'                    => 'Frans',
            'german'                    => 'Duits',
            'hebrew'                    => 'Hebreeuws',
            'hindi'                     => 'Hindi',
            'installation-description'  => 'De installatie van UnoPim omvat doorgaans verschillende stappen. Hier is een algemeen overzicht van het installatieproces voor UnoPim:',
            'wizard-language'           => 'Taal van de installatiewizard',
            'installation-info'         => 'We zijn blij je hier te zien!',
            'installation-title'        => 'Welkom bij Installatie',
            'italian'                   => 'Italiaans',
            'japanese'                  => 'Japanse',
            'persian'                   => 'Perzisch',
            'polish'                    => 'Pools',
            'portuguese'                => 'Braziliaans Portugees',
            'russian'                   => 'Russisch',
            'save-configuration'        => 'Configuratie opslaan',
            'sinhala'                   => 'Singalees',
            'skip'                      => 'Overslaan',
            'spanish'                   => 'Spaans',
            'title'                     => 'UnoPim-installatieprogramma',
            'turkish'                   => 'Turks',
            'ukrainian'                 => 'Oekraïens',
            'webkul'                    => 'Webkul',
        ],
    ],
];
