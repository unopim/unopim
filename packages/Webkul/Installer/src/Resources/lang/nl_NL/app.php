<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Standaard',
            ],

            'attribute-groups' => [
                'description'      => 'Beschrijving',
                'general'          => 'Algemeen',
                'meta-description' => 'Meta beschrijving',
                'price'            => 'Prijs',
                'media'            => 'Media',
            ],

            'attributes' => [
                'brand'                => 'Merk',
                'color'                => 'Kleur',
                'cost'                 => 'Kosten',
                'description'          => 'Beschrijving',
                'featured'             => 'Uitgelicht',
                'guest-checkout'       => 'Afrekenen als gast',
                'height'               => 'Hoogte',
                'image'                => 'Afbeelding',
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
                'tax-category'         => 'Belastingcategorie',
                'url-key'              => 'URL-sleutel',
                'visible-individually' => 'Individueel zichtbaar',
                'weight'               => 'Gewicht',
                'width'                => 'Breedte',
            ],

            'attribute-options' => [
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

        'category' => [
            'categories' => [
                'description' => 'Beschrijving van hoofdcategorie',
                'name'        => 'Wortel',
            ],

            'category_fields' => [
                'name'        => 'Naam',
                'description' => 'Beschrijving',
            ],
        ],

        'core' => [
            'channels' => [
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

        'user' => [
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

            'installation-processing' => [
                'unopim'      => 'Installatie UnoPim',
                'unopim-info' => 'Het maken van de databasetabellen kan even duren',
                'title'       => 'Installatie',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Beheerderspaneel',
                'unopim-forums'             => 'UnoPim-forum',
                'explore-unopim-extensions' => 'Ontdek de UnoPim-extensie',
                'title-info'                => 'UnoPim is succesvol op uw systeem geïnstalleerd.',
                'title'                     => 'Installatie voltooid',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Maak de databasetabel',
                'install-info-button'     => 'Klik op onderstaande knop om',
                'install-info'            => 'UnoPim voor installatie',
                'install'                 => 'Installatie',
                'populate-database-table' => 'Vul de databasetabellen in',
                'start-installation'      => 'Begin met de installatie',
                'title'                   => 'Klaar voor installatie',
            ],

            'start' => [
                'locale'        => 'Lokaal',
                'main'          => 'Begin',
                'select-locale' => 'Selecteer Landinstelling',
                'title'         => 'Uw UnoPim-installatie',
                'welcome-title' => 'Welkom bij UnoPim :version',
            ],

            'server-requirements' => [
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

            'back'                     => 'Rug',
            'unopim-info'              => 'een gemeenschapsproject van',
            'unopim-logo'              => 'UnoPim-logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Doorgaan',
            'installation-description' => 'De installatie van UnoPim omvat doorgaans verschillende stappen. Hier is een algemeen overzicht van het installatieproces voor UnoPim:',
            'wizard-language'          => 'Taal van de installatiewizard',
            'installation-info'        => 'We zijn blij je hier te zien!',
            'installation-title'       => 'Welkom bij Installatie',
            'save-configuration'       => 'Configuratie opslaan',
            'skip'                     => 'Overslaan',
            'title'                    => 'UnoPim-installatieprogramma',
            'webkul'                   => 'Webkul',
        ],
    ],
];
