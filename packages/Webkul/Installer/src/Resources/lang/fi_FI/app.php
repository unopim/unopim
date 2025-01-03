<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Oletus',
            ],

            'attribute-groups'   => [
                'description'       => 'Kuvaus',
                'general'           => 'Yleinen',
                'inventories'       => 'Varastot',
                'meta-description'  => 'Meta Kuvaus',
                'price'             => 'Hinta',
                'technical'         => 'Tekninen',
                'shipping'          => 'Toimitus',
            ],

            'attributes'         => [
                'brand'                => 'Brändi',
                'color'                => 'Väri',
                'cost'                 => 'Kustannus',
                'description'          => 'Kuvaus',
                'featured'             => 'Suositeltu',
                'guest-checkout'       => 'Vierastarkastus',
                'height'               => 'Korkeus',
                'length'               => 'Pituus',
                'manage-stock'         => 'Hallinnoi Varastoa',
                'meta-description'     => 'Meta Kuvaus',
                'meta-keywords'        => 'Meta Avainsanat',
                'meta-title'           => 'Meta Otsikko',
                'name'                 => 'Nimi',
                'new'                  => 'Uusi',
                'price'                => 'Hinta',
                'product-number'       => 'Tuotenumero',
                'short-description'    => 'Lyhyt Kuvaus',
                'size'                 => 'Koko',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Erikoishinta Alkaen',
                'special-price-to'     => 'Erikoishinta Ennalleen',
                'special-price'        => 'Erikoishinta',
                'status'               => 'Status',
                'tax-category'         => 'Veroluokka',
                'url-key'              => 'URL Avain',
                'visible-individually' => 'Näkyy Erikseen',
                'weight'               => 'Paino',
                'width'                => 'Leveys',
            ],

            'attribute-options'  => [
                'black'  => 'Musta',
                'green'  => 'Vihreä',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Punainen',
                's'      => 'S',
                'white'  => 'Valkoinen',
                'xl'     => 'XL',
                'yellow' => 'Keltainen',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'Juuri Kategorian Kuvaus',
                'name'        => 'Juuri',
            ],

            'category_fields' => [
                'name'        => 'Nimi',
                'description' => 'Kuvaus',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'Tietoja Meistä Sivun Sisältö',
                    'title'   => 'Tietoja Meistä',
                ],

                'contact-us'       => [
                    'content' => 'Ota Yhteyttä Sivun Sisältö',
                    'title'   => 'Ota Yhteyttä',
                ],

                'customer-service' => [
                    'content' => 'Asiakaspalvelun Sivun Sisältö',
                    'title'   => 'Asiakaspalvelu',
                ],

                'payment-policy'   => [
                    'content' => 'Maksupolitiikan Sivun Sisältö',
                    'title'   => 'Maksupolitiikka',
                ],

                'privacy-policy'   => [
                    'content' => 'Yksityisyydensuojan Sivun Sisältö',
                    'title'   => 'Yksityisyys',
                ],

                'refund-policy'    => [
                    'content' => 'Palautuspolitiikan Sivun Sisältö',
                    'title'   => 'Palautuspolitiikka',
                ],

                'return-policy'    => [
                    'content' => 'Palautuskäytännön Sivun Sisältö',
                    'title'   => 'Palautuskäytäntö',
                ],

                'shipping-policy'  => [
                    'content' => 'Toimituskäytännön Sivun Sisältö',
                    'title'   => 'Toimituskäytäntö',
                ],

                'terms-conditions' => [
                    'content' => 'Ehdot ja Käytännöt Sivun Sisältö',
                    'title'   => 'Ehdot ja Käytännöt',
                ],

                'terms-of-use'     => [
                    'content' => 'Käyttöehdot Sivun Sisältö',
                    'title'   => 'Käyttöehdot',
                ],

                'whats-new'        => [
                    'content' => 'Uutuudet Sivun Sisältö',
                    'title'   => 'Uutuudet',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'Demo Store',
                'meta-keywords'    => 'Demo Store Meta Avainsanat',
                'meta-description' => 'Demo Store Meta Kuvaus',
                'name'             => 'Oletus',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israelin Shekeli',
                'CNY' => 'Kiinan Yuan',
                'EUR' => 'EURO',
                'GBP' => 'Punnan Sterling',
                'INR' => 'Intian Rupee',
                'IRR' => 'Iran Rial',
                'JPY' => 'Japanin Jeni',
                'RUB' => 'Venäjän Ruble',
                'SAR' => 'Saudi Riyal',
                'TRY' => 'Turkin Lira',
                'UAH' => 'Ukrainan Hryvnia',
                'USD' => 'US Dollari',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => 'Yleinen',
                'guest'     => 'Vieras',
                'wholesale' => 'Tukkukauppa',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Oletus',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'Kaikki Tuotteet',

                    'options' => [
                        'title' => 'Kaikki Tuotteet',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'Näytä Kaikki',
                        'description' => 'Tervetuloa Uusiin Bold Kokoelmiin! Kohenna tyyliäsi rohkeilla malleilla ja kirkkailla väreillä. Tutustu silmiinpistäviin kuvioihin ja rohkeisiin väreihin, jotka määrittelevät vaatekaapin uudelleen. Ole valmis omaksumaan poikkeukselliset!',
                        'title'       => 'Valmistaudu Uusiin Bold Kokoelmiin!',
                    ],

                    'name'    => 'Bold Kokoelmat',
                ],

                'categories-collections' => [
                    'name' => 'Luokkien Kokoelmat',
                ],

                'featured-collections'   => [
                    'name'    => 'Esitellyt Kokoelmat',

                    'options' => [
                        'title' => 'Esitellyt Tuotteet',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'Jalkaliittymät',

                    'options' => [
                        'about-us'         => 'Tietoja Meistä',
                        'contact-us'       => 'Ota Yhteyttä',
                        'customer-service' => 'Asiakaspalvelu',
                        'payment-policy'   => 'Maksupolitiikka',
                        'privacy-policy'   => 'Yksityisyys',
                        'refund-policy'    => 'Palautuspolitiikka',
                        'return-policy'    => 'Palautuskäytäntö',
                        'shipping-policy'  => 'Toimituskäytäntö',
                        'terms-conditions' => 'Ehdot ja Käytännöt',
                        'terms-of-use'     => 'Käyttöehdot',
                        'whats-new'        => 'Uutuudet',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Kokoelmat',
                        'sub-title-2' => 'Kokoelmat',
                        'title'       => 'Peli Uusilla Lisäyksillämme!',
                    ],

                    'name'    => 'Peli Astia',
                ],

                'image-carousel'         => [
                    'name'    => 'Kuva-Galleria',

                    'sliders' => [
                        'title' => 'Valmistaudu Uuteen Kokoelmaan',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'Uudet Tuotteet',

                    'options' => [
                        'title' => 'Uudet Tuotteet',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'SAAVUTTA 40% ALENNUS ENSIMMÄISELLÄ TILAUKSELLASI, OSTA NYT',
                    ],

                    'name' => 'Tarjous Tiedot',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'Ei kustannuksia EMI kaikkien pääluottokorttien osalta',
                        'free-shipping-info'   => 'Vapaat toimitukset kaikille tilauksille',
                        'product-replace-info' => 'Helppo tuotevaihto saatavilla!',
                        'time-support-info'    => 'Omistautunut 24/7 tuki chatissa ja sähköpostitse',
                    ],

                    'name'        => 'Palvelut Sisältö',

                    'title'       => [
                        'emi-available'   => 'EMI Saatavilla',
                        'free-shipping'   => 'Vapaa Toimitus',
                        'product-replace' => 'Tuote Vaihto',
                        'time-support'    => '24/7 Tuki',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Kokoelmat',
                        'sub-title-2' => 'Kokoelmat',
                        'sub-title-3' => 'Kokoelmat',
                        'sub-title-4' => 'Kokoelmat',
                        'sub-title-5' => 'Kokoelmat',
                        'sub-title-6' => 'Kokoelmat',
                        'title'       => 'Peli Uusilla Lisäyksillämme!',
                    ],

                    'name'    => 'Ylä Kokoelmat',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'Tämä rooli antaa käyttäjälle kaikki oikeudet',
                'name'        => 'Ylläpitäjä',
            ],

            'users' => [
                'name' => 'Esimerkki',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Ylläpitäjä',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Vahvista salasana',
                'email-address'    => 'admin@example.com',
                'email'            => 'Sähköposti',
                'password'         => 'Salasana',
                'title'            => 'Luo Ylläpitäjä',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Sallitut valuutat',
                'allowed-locales'     => 'Sallitut paikalliset asetukset',
                'application-name'    => 'Sovelluksen nimi',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Kiinan Yuan (CNY)',
                'database-connection' => 'Tietokantayhteys',
                'database-hostname'   => 'Tietokannan isäntänimi',
                'database-name'       => 'Tietokannan nimi',
                'database-password'   => 'Tietokannan salasana',
                'database-port'       => 'Tietokannan portti',
                'database-prefix'     => 'Tietokannan etuliite',
                'database-username'   => 'Tietokannan käyttäjänimi',
                'default-currency'    => 'Oletusvaluutta',
                'default-locale'      => 'Oletuspaikalliset asetukset',
                'default-timezone'    => 'Oletusaikavyöhyke',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Oletus-URL',
                'dirham'              => 'Dirhami (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iranin Rial (IRR)',
                'israeli'             => 'Israelilainen Sekeli (ILS)',
                'japanese-yen'        => 'Japanin Jeni (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Englannin Punta (GBP)',
                'rupee'               => 'Intian Rupia (INR)',
                'russian-ruble'       => 'Venäjän Rupla (RUB)',
                'saudi'               => 'Saudi-Riyal (SAR)',
                'select-timezone'     => 'Valitse aikavyöhyke',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Tietokannan Konfigurointi',
                'turkish-lira'        => 'Turkin Liira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrainan Hryvnia (UAH)',
                'usd'                 => 'Yhdysvaltain Dollari (USD)',
                'warning-message'     => 'Varoitus! Järjestelmän oletuskielen ja valuutan asetuksia ei voi myöhemmin muuttaa.',
            ],

            'installation-processing' => [
                'unopim'            => 'UnoPim Asennus',
                'unopim-info'       => 'Tietokantataulukoiden luominen, tämä voi kestää hetken',
                'title'             => 'Asennus',
            ],

            'installation-completed' => [
                'admin-panel'                   => 'Ylläpitäjän Paneeli',
                'unopim-forums'                 => 'UnoPim Foorumit',
                'explore-unopim-extensions'     => 'Tutustu UnoPim Laajennuksiin',
                'title-info'                    => 'UnoPim on onnistuneesti asennettu järjestelmään.',
                'title'                         => 'Asennus Valmis',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Luo tietokantataulut',
                'install-info-button'     => 'Klikkaa alla olevaa painiketta aloittaaksesi',
                'install-info'            => 'UnoPim Asennuksen',
                'install'                 => 'Asennus',
                'populate-database-table' => 'Täytä tietokantataulut',
                'start-installation'      => 'Aloita Asennus',
                'title'                   => 'Valmis Asennukseen',
            ],

            'start' => [
                'locale'        => 'Paikalliset asetukset',
                'main'          => 'Aloita',
                'select-locale' => 'Valitse paikalliset asetukset',
                'title'         => 'UnoPim Asennus',
                'welcome-title' => 'Tervetuloa UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Kalenteri',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Tiedoston tiedot',
                'filter'      => 'Suodatin',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Kansainvälinen',
                'json'        => 'JSON',
                'mbstring'    => 'Monibittinen merkkijono',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 tai uudempi',
                'php'         => 'PHP',
                'session'     => 'Istunto',
                'title'       => 'Järjestelmävaatimukset',
                'tokenizer'   => 'Tokenisaattori',
                'xml'         => 'XML',
            ],

            'arabic'                    => 'Arabia',
            'back'                      => 'Takaisin',
            'unopim-info'               => 'Yhteisöprojekti',
            'unopim-logo'               => 'UnoPim Logo',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'Bengali',
            'chinese'                   => 'Kiina',
            'continue'                  => 'Jatka',
            'dutch'                     => 'Hollanti',
            'english'                   => 'Englanti',
            'french'                    => 'Ranska',
            'german'                    => 'Saksa',
            'hebrew'                    => 'Heprea',
            'hindi'                     => 'Hindi',
            'installation-description'  => 'UnoPimin asennus sisältää useita vaiheita. Tässä yleiskatsaus:',
            'wizard-language'           => 'Asennusohjelman kieli',
            'installation-info'         => 'Mukava nähdä sinut täällä!',
            'installation-title'        => 'Tervetuloa Asennukseen',
            'italian'                   => 'Italia',
            'japanese'                  => 'Japani',
            'persian'                   => 'Persia',
            'polish'                    => 'Puola',
            'portuguese'                => 'Portugalin Portugali',
            'russian'                   => 'Venäjä',
            'save-configuration'        => 'Tallenna Konfiguraatio',
            'sinhala'                   => 'Sinhala',
            'skip'                      => 'Ohita',
            'spanish'                   => 'Espanja',
            'title'                     => 'UnoPim Asennusohjelma',
            'turkish'                   => 'Turkki',
            'ukrainian'                 => 'Ukraina',
            'webkul'                    => 'Webkul',
        ],
    ],
];
