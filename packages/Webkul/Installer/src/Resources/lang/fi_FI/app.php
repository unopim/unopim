<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Oletus',
            ],

            'attribute-groups' => [
                'description'      => 'Kuvaus',
                'general'          => 'Yleinen',
                'meta-description' => 'Meta kuvaus',
                'price'            => 'Hinta',
                'media'            => 'Media',
            ],

            'attributes' => [
                'brand'                => 'Brändi',
                'color'                => 'Väri',
                'cost'                 => 'Kustannus',
                'description'          => 'Kuvaus',
                'featured'             => 'Suositeltu',
                'guest-checkout'       => 'Vierastarkastus',
                'height'               => 'Korkeus',
                'image'                => 'Kuva',
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
                'tax-category'         => 'Veroluokka',
                'url-key'              => 'URL Avain',
                'visible-individually' => 'Näkyy Erikseen',
                'weight'               => 'Paino',
                'width'                => 'Leveys',
            ],

            'attribute-options' => [
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

        'category' => [
            'categories' => [
                'description' => 'Juuri Kategorian Kuvaus',
                'name'        => 'Juuri',
            ],

            'category_fields' => [
                'name'        => 'Nimi',
                'description' => 'Kuvaus',
            ],
        ],

        'core' => [
            'channels' => [
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

        'user' => [
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
        'middleware' => [
            'already-installed' => 'Sovellus on jo asennettu.',
        ],

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
                'unopim'      => 'UnoPim Asennus',
                'unopim-info' => 'Tietokantataulukoiden luominen, tämä voi kestää hetken',
                'title'       => 'Asennus',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Ylläpitäjän Paneeli',
                'unopim-forums'             => 'UnoPim Foorumit',
                'explore-unopim-extensions' => 'Tutustu UnoPim Laajennuksiin',
                'title-info'                => 'UnoPim on onnistuneesti asennettu järjestelmään.',
                'title'                     => 'Asennus Valmis',
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

            'back'                     => 'Takaisin',
            'unopim-info'              => 'Yhteisöprojekti',
            'unopim-logo'              => 'UnoPim Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Jatka',
            'installation-description' => 'UnoPimin asennus sisältää useita vaiheita. Tässä yleiskatsaus:',
            'wizard-language'          => 'Asennusohjelman kieli',
            'installation-info'        => 'Mukava nähdä sinut täällä!',
            'installation-title'       => 'Tervetuloa Asennukseen',
            'save-configuration'       => 'Tallenna Konfiguraatio',
            'skip'                     => 'Ohita',
            'title'                    => 'UnoPim Asennusohjelma',
            'webkul'                   => 'Webkul',
        ],
    ],
];
