<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Standard',
            ],

            'attribute-groups' => [
                'description'      => 'Beschreibung',
                'general'          => 'Allgemein',
                'meta-description' => 'Meta-Beschreibung',
                'price'            => 'Preis',
                'media'            => 'Medien',
            ],

            'attributes' => [
                'brand'                => 'Marke',
                'color'                => 'Farbe',
                'cost'                 => 'Kosten',
                'description'          => 'Beschreibung',
                'featured'             => 'Hervorgehoben',
                'guest-checkout'       => 'Gastkasse',
                'height'               => 'Höhe',
                'image'                => 'Bild',
                'length'               => 'Länge',
                'manage-stock'         => 'Lagerbestand verwalten',
                'meta-description'     => 'Meta-Beschreibung',
                'meta-keywords'        => 'Meta-Schlüsselwörter',
                'meta-title'           => 'Metatitel',
                'name'                 => 'Name',
                'new'                  => 'Neu',
                'price'                => 'Preis',
                'product-number'       => 'Produktnummer',
                'short-description'    => 'Kurzbeschreibung',
                'size'                 => 'Größe',
                'sku'                  => 'Artikelnummer',
                'special-price-from'   => 'Sonderpreis ab',
                'special-price-to'     => 'Sonderpreis bis',
                'special-price'        => 'Sonderpreis',
                'tax-category'         => 'Steuerkategorie',
                'url-key'              => 'URL-Schlüssel',
                'visible-individually' => 'Einzeln sichtbar',
                'weight'               => 'Gewicht',
                'width'                => 'Breite',
            ],

            'attribute-options' => [
                'black'  => 'Schwarz',
                'green'  => 'Grün',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Rot',
                's'      => 'S',
                'white'  => 'Weiß',
                'xl'     => 'XL',
                'yellow' => 'Gelb',
            ],
        ],

        'category' => [
            'categories' => [
                'description' => 'Beschreibung der Stammkategorie',
                'name'        => 'Wurzel',
            ],

            'category_fields' => [
                'name'        => 'Name',
                'description' => 'Beschreibung',
            ],
        ],

        'core' => [
            'channels' => [
                'meta-title'       => 'Demo-Shop',
                'meta-keywords'    => 'Meta-Schlüsselwort für den Demo-Shop',
                'meta-description' => 'Meta-Beschreibung des Demo-Shops',
                'name'             => 'Standard',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israelischer Schekel',
                'CNY' => 'Chinesischer Yuan',
                'EUR' => 'EURO',
                'GBP' => 'Pfund Sterling',
                'INR' => 'Indische Rupie',
                'IRR' => 'Iranischer Rial',
                'JPY' => 'Japanischer Yen',
                'RUB' => 'Russischer Rubel',
                'SAR' => 'Saudi-Riyal',
                'TRY' => 'Türkische Lira',
                'UAH' => 'Ukrainische Griwna',
                'USD' => 'US-Dollar',
            ],
        ],

        'user' => [
            'roles' => [
                'description' => 'Benutzer dieser Rolle haben sämtlichen Zugriff',
                'name'        => 'Administrator',
            ],

            'users' => [
                'name' => 'Beispiel',
            ],
        ],
    ],

    'installer' => [

        'middleware' => [
            'already-installed' => 'Anwendung ist bereits installiert.',
        ],

        'index' => [
            'create-administrator' => [
                'admin'            => 'Admin',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Passwort bestätigen',
                'email-address'    => 'admin@example.com',
                'email'            => 'E-Mail',
                'password'         => 'Passwort',
                'title'            => 'Erstellen Sie einen Administrator',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Zulässige Währungen',
                'allowed-locales'     => 'Zulässige Gebietsschemata',
                'application-name'    => 'Anwendungsname',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Chinesischer Yuan (CNY)',
                'database-connection' => 'Datenbankverbindung',
                'database-hostname'   => 'Datenbank-Hostname',
                'database-name'       => 'Datenbankname',
                'database-password'   => 'Datenbankpasswort',
                'database-port'       => 'Datenbankport',
                'database-prefix'     => 'Datenbankpräfix',
                'database-username'   => 'Datenbank-Benutzername',
                'default-currency'    => 'Standardwährung',
                'default-locale'      => 'Standardgebietsschema',
                'default-timezone'    => 'Standardzeitzone',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Standard-URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iranischer Rial (IRR)',
                'israeli'             => 'Israelischer Schekel (AFN)',
                'japanese-yen'        => 'Japanischer Yen (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Pfund Sterling (GBP)',
                'rupee'               => 'Indische Rupie (INR)',
                'russian-ruble'       => 'Russischer Rubel (RUB)',
                'saudi'               => 'Saudi-Riyal (SAR)',
                'select-timezone'     => 'Wählen Sie Zeitzone',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Datenbankkonfiguration',
                'turkish-lira'        => 'Türkische Lira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrainische Griwna (UAH)',
                'usd'                 => 'US-Dollar (USD)',
                'warning-message'     => 'Vorsicht! Die Einstellungen für Ihre Standardsystemsprachen sowie die Standardwährung sind dauerhaft und können nie wieder geändert werden.',
            ],

            'installation-processing' => [
                'unopim'      => 'UnoPim-Installation',
                'unopim-info' => 'Das Erstellen der Datenbanktabellen kann einige Momente dauern',
                'title'       => 'Installation',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Admin-Panel',
                'unopim-forums'             => 'UnoPim-Forum',
                'explore-unopim-extensions' => 'Entdecken Sie die UnoPim-Erweiterung',
                'title-info'                => 'UnoPim wurde erfolgreich auf Ihrem System installiert.',
                'title'                     => 'Installation abgeschlossen',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Erstellen Sie die Datenbanktabelle',
                'install-info-button'     => 'Klicken Sie auf die Schaltfläche unten, um',
                'install-info'            => 'UnoPim zur Installation',
                'install'                 => 'Installation',
                'populate-database-table' => 'Füllen Sie die Datenbanktabellen',
                'start-installation'      => 'Starten Sie die Installation',
                'title'                   => 'Bereit zur Installation',
            ],

            'start' => [
                'locale'        => 'Gebietsschema',
                'main'          => 'Start',
                'select-locale' => 'Wählen Sie Gebietsschema aus',
                'title'         => 'Ihre UnoPim-Installation',
                'welcome-title' => 'Willkommen bei UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Kalender',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'dom',
                'fileinfo'    => 'fileInfo',
                'filter'      => 'Filter',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'intl',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'openSL',
                'pcre'        => 'pcre',
                'pdo'         => 'pdo',
                'php-version' => '8,2 oder höher',
                'php'         => 'PHP',
                'session'     => 'Sitzung',
                'title'       => 'Systemanforderungen',
                'tokenizer'   => 'Tokenizer',
                'xml'         => 'XML',
            ],

            'back'                     => 'Zurück',
            'unopim-info'              => 'ein Community-Projekt von',
            'unopim-logo'              => 'UnoPim-Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Weitermachen',
            'installation-description' => 'Die Installation von UnoPim umfasst normalerweise mehrere Schritte. Hier ist ein allgemeiner Überblick über den Installationsprozess für UnoPim:',
            'wizard-language'          => 'Sprache des Installationsassistenten',
            'installation-info'        => 'Wir freuen uns, Sie hier zu sehen!',
            'installation-title'       => 'Willkommen bei der Installation',
            'save-configuration'       => 'Konfiguration speichern',
            'skip'                     => 'Überspringen',
            'title'                    => 'UnoPim-Installationsprogramm',
            'webkul'                   => 'Webkul',
        ],
    ],
];
