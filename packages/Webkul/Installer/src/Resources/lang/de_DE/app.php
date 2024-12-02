<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Standard',
            ],

            'attribute-groups'   => [
                'description'       => 'Beschreibung',
                'general'           => 'Allgemein',
                'inventories'       => 'Vorräte',
                'meta-description'  => 'Meta-Beschreibung',
                'price'             => 'Preis',
                'technical'         => 'Technisch',
                'shipping'          => 'Versand',
            ],

            'attributes'         => [
                'brand'                => 'Marke',
                'color'                => 'Farbe',
                'cost'                 => 'Kosten',
                'description'          => 'Beschreibung',
                'featured'             => 'Hervorgehoben',
                'guest-checkout'       => 'Gastkasse',
                'height'               => 'Höhe',
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
                'status'               => 'Status',
                'tax-category'         => 'Steuerkategorie',
                'url-key'              => 'URL-Schlüssel',
                'visible-individually' => 'Einzeln sichtbar',
                'weight'               => 'Gewicht',
                'width'                => 'Breite',
            ],

            'attribute-options'  => [
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

        'category'  => [
            'categories' => [
                'description' => 'Beschreibung der Stammkategorie',
                'name'        => 'Wurzel',
            ],

            'category_fields' => [
                'name'        => 'Name',
                'description' => 'Beschreibung',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'Über uns Seiteninhalt',
                    'title'   => 'Über uns',
                ],

                'contact-us'       => [
                    'content' => 'Kontaktieren Sie uns Seiteninhalt',
                    'title'   => 'Kontaktieren Sie uns',
                ],

                'customer-service' => [
                    'content' => 'Inhalt der Kundendienstseite',
                    'title'   => 'Kundendienst',
                ],

                'payment-policy'   => [
                    'content' => 'Seiteninhalt der Zahlungsrichtlinien',
                    'title'   => 'Zahlungsbedingungen',
                ],

                'privacy-policy'   => [
                    'content' => 'Seiteninhalt der Datenschutzrichtlinie',
                    'title'   => 'Datenschutzrichtlinie',
                ],

                'refund-policy'    => [
                    'content' => 'Seiteninhalt der Rückerstattungsrichtlinie',
                    'title'   => 'Rückerstattungsrichtlinie',
                ],

                'return-policy'    => [
                    'content' => 'Inhalt der Rückgaberichtlinienseite',
                    'title'   => 'Rückgaberecht',
                ],

                'shipping-policy'  => [
                    'content' => 'Seiteninhalt der Versandrichtlinien',
                    'title'   => 'Versandbedingungen',
                ],

                'terms-conditions' => [
                    'content' => 'Seiteninhalt der Allgemeinen Geschäftsbedingungen',
                    'title'   => 'Allgemeine Geschäftsbedingungen',
                ],

                'terms-of-use'     => [
                    'content' => 'Nutzungsbedingungen Seiteninhalt',
                    'title'   => 'Nutzungsbedingungen',
                ],

                'whats-new'        => [
                    'content' => 'Was gibt es Neues? Seiteninhalt',
                    'title'   => 'Was ist neu',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
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

        'customer'  => [
            'customer-groups' => [
                'general'   => 'Allgemein',
                'guest'     => 'Gast',
                'wholesale' => 'Großhandel',
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
                    'name'    => 'Alle Produkte',

                    'options' => [
                        'title' => 'Alle Produkte',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'Alle anzeigen',
                        'description' => 'Wir stellen unsere neuen, auffälligen Kollektionen vor! Werten Sie Ihren Stil mit gewagten Designs und lebendigen Statements auf. Entdecken Sie auffällige Muster und kräftige Farben, die Ihre Garderobe neu definieren. Machen Sie sich bereit für das Außergewöhnliche!',
                        'title'       => 'Machen Sie sich bereit für unsere neuen Bold-Kollektionen!',
                    ],

                    'name'    => 'Mutige Kollektionen',
                ],

                'categories-collections' => [
                    'name' => 'Kategorien Sammlungen',
                ],

                'featured-collections'   => [
                    'name'    => 'Ausgewählte Sammlungen',

                    'options' => [
                        'title' => 'Ausgewählte Produkte',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'Fußzeilen-Links',

                    'options' => [
                        'about-us'         => 'Über uns',
                        'contact-us'       => 'Kontaktieren Sie uns',
                        'customer-service' => 'Kundendienst',
                        'payment-policy'   => 'Zahlungsbedingungen',
                        'privacy-policy'   => 'Datenschutzrichtlinie',
                        'refund-policy'    => 'Rückerstattungsrichtlinie',
                        'return-policy'    => 'Rückgaberecht',
                        'shipping-policy'  => 'Versandbedingungen',
                        'terms-conditions' => 'Allgemeine Geschäftsbedingungen',
                        'terms-of-use'     => 'Nutzungsbedingungen',
                        'whats-new'        => 'Was ist neu',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Unsere Sammlungen',
                        'sub-title-2' => 'Unsere Sammlungen',
                        'title'       => 'Das Spiel mit unseren Neuzugängen!',
                    ],

                    'name'    => 'Spielcontainer',
                ],

                'image-carousel'         => [
                    'name'    => 'Bildkarussell',

                    'sliders' => [
                        'title' => 'Machen Sie sich bereit für die neue Kollektion',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'Neue Produkte',

                    'options' => [
                        'title' => 'Neue Produkte',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'Erhalten Sie BIS ZU 40 % RABATT auf Ihre erste Bestellung JETZT EINKAUFEN',
                    ],

                    'name' => 'Angebotsinformationen',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'Kostenloses EMI für alle gängigen Kreditkarten verfügbar',
                        'free-shipping-info'   => 'Profitieren Sie von kostenlosem Versand für alle Bestellungen',
                        'product-replace-info' => 'Einfacher Produktaustausch möglich!',
                        'time-support-info'    => 'Engagierter 24/7-Support per Chat und E-Mail',
                    ],

                    'name'        => 'Inhalt der Dienste',

                    'title'       => [
                        'emi-available'   => 'Emi verfügbar',
                        'free-shipping'   => 'Kostenloser Versand',
                        'product-replace' => 'Produkt ersetzen',
                        'time-support'    => '24/7-Support',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Unsere Sammlungen',
                        'sub-title-2' => 'Unsere Sammlungen',
                        'sub-title-3' => 'Unsere Sammlungen',
                        'sub-title-4' => 'Unsere Sammlungen',
                        'sub-title-5' => 'Unsere Sammlungen',
                        'sub-title-6' => 'Unsere Sammlungen',
                        'title'       => 'Das Spiel mit unseren Neuzugängen!',
                    ],

                    'name'    => 'Top-Sammlungen',
                ],
            ],
        ],

        'user'      => [
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

            'installation-processing'   => [
                'unopim'            => 'UnoPim-Installation',
                'unopim-info'       => 'Das Erstellen der Datenbanktabellen kann einige Momente dauern',
                'title'             => 'Installation',
            ],

            'installation-completed'    => [
                'admin-panel'                   => 'Admin-Panel',
                'unopim-forums'                 => 'UnoPim-Forum',
                'explore-unopim-extensions'     => 'Entdecken Sie die UnoPim-Erweiterung',
                'title-info'                    => 'UnoPim wurde erfolgreich auf Ihrem System installiert.',
                'title'                         => 'Installation abgeschlossen',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => 'Erstellen Sie die Datenbanktabelle',
                'install-info-button'     => 'Klicken Sie auf die Schaltfläche unten, um',
                'install-info'            => 'UnoPim zur Installation',
                'install'                 => 'Installation',
                'populate-database-table' => 'Füllen Sie die Datenbanktabellen',
                'start-installation'      => 'Starten Sie die Installation',
                'title'                   => 'Bereit zur Installation',
            ],

            'start'                     => [
                'locale'        => 'Gebietsschema',
                'main'          => 'Start',
                'select-locale' => 'Wählen Sie Gebietsschema aus',
                'title'         => 'Ihre UnoPim-Installation',
                'welcome-title' => 'Willkommen bei UnoPim :version',
            ],

            'server-requirements'       => [
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

            'arabic'                    => 'Arabisch',
            'back'                      => 'Zurück',
            'unopim-info'               => 'ein Community-Projekt von',
            'unopim-logo'               => 'UnoPim-Logo',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'Bengali',
            'chinese'                   => 'chinesisch',
            'continue'                  => 'Weitermachen',
            'dutch'                     => 'Niederländisch',
            'english'                   => 'Englisch',
            'french'                    => 'Französisch',
            'german'                    => 'Deutsch',
            'hebrew'                    => 'hebräisch',
            'hindi'                     => 'Hindi',
            'installation-description'  => 'Die Installation von UnoPim umfasst normalerweise mehrere Schritte. Hier ist ein allgemeiner Überblick über den Installationsprozess für UnoPim:',
            'wizard-language'           => 'Sprache des Installationsassistenten',
            'installation-info'         => 'Wir freuen uns, Sie hier zu sehen!',
            'installation-title'        => 'Willkommen bei der Installation',
            'italian'                   => 'Italienisch',
            'japanese'                  => 'japanisch',
            'persian'                   => 'persisch',
            'polish'                    => 'Polieren',
            'portuguese'                => 'Brasilianisches Portugiesisch',
            'russian'                   => 'Russisch',
            'save-configuration'        => 'Konfiguration speichern',
            'sinhala'                   => 'Singhalesisch',
            'skip'                      => 'Überspringen',
            'spanish'                   => 'Spanisch',
            'title'                     => 'UnoPim-Installationsprogramm',
            'turkish'                   => 'Türkisch',
            'ukrainian'                 => 'ukrainisch',
            'webkul'                    => 'Webkul',
        ],
    ],
];
