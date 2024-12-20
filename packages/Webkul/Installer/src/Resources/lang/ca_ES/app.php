<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Default',
            ],

            'attribute-groups'   => [
                'description'       => 'Description',
                'general'           => 'General',
                'inventories'       => 'Inventories',
                'meta-description'  => 'Meta Description',
                'price'             => 'Price',
                'technical'         => 'Technical',
                'shipping'          => 'Shipping',
            ],

            'attributes'         => [
                'brand'                => 'Brand',
                'color'                => 'Color',
                'cost'                 => 'Cost',
                'description'          => 'Description',
                'featured'             => 'Featured',
                'guest-checkout'       => 'Guest Checkout',
                'height'               => 'Height',
                'length'               => 'Length',
                'manage-stock'         => 'Manage Stock',
                'meta-description'     => 'Meta Description',
                'meta-keywords'        => 'Meta Keywords',
                'meta-title'           => 'Meta Title',
                'name'                 => 'Name',
                'new'                  => 'New',
                'price'                => 'Price',
                'product-number'       => 'Product Number',
                'short-description'    => 'Short Description',
                'size'                 => 'Size',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Special Price From',
                'special-price-to'     => 'Special Price To',
                'special-price'        => 'Special Price',
                'status'               => 'Status',
                'tax-category'         => 'Tax Category',
                'url-key'              => 'URL Key',
                'visible-individually' => 'Visible Individually',
                'weight'               => 'Weight',
                'width'                => 'Width',
            ],

            'attribute-options'  => [
                'black'  => 'Black',
                'green'  => 'Green',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Red',
                's'      => 'S',
                'white'  => 'White',
                'xl'     => 'XL',
                'yellow' => 'Yellow',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'Root Category Description',
                'name'        => 'Root',
            ],

            'category_fields' => [
                'name'        => 'Name',
                'description' => 'Description',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'About Us Page Content',
                    'title'   => 'About Us',
                ],

                'contact-us'       => [
                    'content' => 'Contact Us Page Content',
                    'title'   => 'Contact Us',
                ],

                'customer-service' => [
                    'content' => 'Customer Service Page Content',
                    'title'   => 'Customer Service',
                ],

                'payment-policy'   => [
                    'content' => 'Payment Policy Page Content',
                    'title'   => 'Payment Policy',
                ],

                'privacy-policy'   => [
                    'content' => 'Privacy Policy Page Content',
                    'title'   => 'Privacy Policy',
                ],

                'refund-policy'    => [
                    'content' => 'Refund Policy Page Content',
                    'title'   => 'Refund Policy',
                ],

                'return-policy'    => [
                    'content' => 'Return Policy Page Content',
                    'title'   => 'Return Policy',
                ],

                'shipping-policy'  => [
                    'content' => 'Shipping Policy Page Content',
                    'title'   => 'Shipping Policy',
                ],

                'terms-conditions' => [
                    'content' => 'Terms & Conditions Page Content',
                    'title'   => 'Terms & Conditions',
                ],

                'terms-of-use'     => [
                    'content' => 'Terms of Use Page Content',
                    'title'   => 'Terms of Use',
                ],

                'whats-new'        => [
                    'content' => 'What\'s New page content',
                    'title'   => 'What\'s New',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'Demo store',
                'meta-keywords'    => 'Demo store meta keyword',
                'meta-description' => 'Demo store meta description',
                'name'             => 'Default',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Israeli Shekel',
                'CNY' => 'Chinese Yuan',
                'EUR' => 'EURO',
                'GBP' => 'Pound Sterling',
                'INR' => 'Indian Rupee',
                'IRR' => 'Iranian Rial',
                'JPY' => 'Japanese Yen',
                'RUB' => 'Russian Ruble',
                'SAR' => 'Saudi Riyal',
                'TRY' => 'Turkish Lira',
                'UAH' => 'Ukrainian Hryvnia',
                'USD' => 'US Dollar',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => 'General',
                'guest'     => 'Guest',
                'wholesale' => 'Wholesale',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Default',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'All Products',

                    'options' => [
                        'title' => 'All Products',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'View All',
                        'description' => 'Introducing Our New Bold Collections! Elevate your style with daring designs and vibrant statements. Explore striking patterns and bold colors that redefine your wardrobe. Get ready to embrace the extraordinary!',
                        'title'       => 'Get Ready for our new Bold Collections!',
                    ],

                    'name'    => 'Bold Collections',
                ],

                'categories-collections' => [
                    'name' => 'Categories Collections',
                ],

                'featured-collections'   => [
                    'name'    => 'Featured Collections',

                    'options' => [
                        'title' => 'Featured Products',
                    ],
                ],

                'footer-links'           => [
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

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Our Collections',
                        'sub-title-2' => 'Our Collections',
                        'title'       => 'The game with our new additions!',
                    ],

                    'name'    => 'Game Container',
                ],

                'image-carousel'         => [
                    'name'    => 'Image Carousel',

                    'sliders' => [
                        'title' => 'Get Ready For New Collection',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'New Products',

                    'options' => [
                        'title' => 'New Products',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'Get UPTO 40% OFF on your 1st order SHOP NOW',
                    ],

                    'name' => 'Offer Information',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'No cost EMI available on all major credit cards',
                        'free-shipping-info'   => 'Enjoy free shipping on all orders',
                        'product-replace-info' => 'Easy Product Replacement Available!',
                        'time-support-info'    => 'Dedicated 24/7 support via chat and email',
                    ],

                    'name'        => 'Services Content',

                    'title'       => [
                        'emi-available'   => 'Emi Available',
                        'free-shipping'   => 'Free Shipping',
                        'product-replace' => 'Product Replace',
                        'time-support'    => '24/7 Support',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Our Collections',
                        'sub-title-2' => 'Our Collections',
                        'sub-title-3' => 'Our Collections',
                        'sub-title-4' => 'Our Collections',
                        'sub-title-5' => 'Our Collections',
                        'sub-title-6' => 'Our Collections',
                        'title'       => 'The game with our new additions!',
                    ],

                    'name'    => 'Top Collections',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'This role users will have all the access',
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
                'admin'            => 'Administrador',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Confirma la contrasenya',
                'email-address'    => 'admin@example.com',
                'email'            => 'Correu electrònic',
                'password'         => 'Contrasenya',
                'title'            => 'Crea administrador',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Monedes permeses',
                'allowed-locales'     => 'Idiomes permesos',
                'application-name'    => 'Nom de l’aplicació',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Iuan xinès (CNY)',
                'database-connection' => 'Connexió a la base de dades',
                'database-hostname'   => 'Nom del servidor de base de dades',
                'database-name'       => 'Nom de la base de dades',
                'database-password'   => 'Contrasenya de la base de dades',
                'database-port'       => 'Port de la base de dades',
                'database-prefix'     => 'Prefix de la base de dades',
                'database-username'   => 'Usuari de la base de dades',
                'default-currency'    => 'Moneda predeterminada',
                'default-locale'      => 'Idioma predeterminat',
                'default-timezone'    => 'Zona horària predeterminada',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'URL predeterminada',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Rial iranià (IRR)',
                'israeli'             => 'Nou xéquel israelià (ILS)',
                'japanese-yen'        => 'Ien japonès (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Lliura esterlina (GBP)',
                'rupee'               => 'Rupia índia (INR)',
                'russian-ruble'       => 'Ruble rus (RUB)',
                'saudi'               => 'Rial saudita (SAR)',
                'select-timezone'     => 'Selecciona zona horària',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Configuració de la base de dades',
                'turkish-lira'        => 'Lira turca (TRY)',
                'ukrainian-hryvnia'   => 'Hrívnia ucraïnesa (UAH)',
                'usd'                 => 'Dòlar estatunidenc (USD)',
                'warning-message'     => 'Atenció! Els ajustos per als idiomes predeterminats i la moneda són permanents i no es poden canviar mai més.',
            ],

            'installation-processing' => [
                'unopim'            => 'Instal·lació d’UnoPim',
                'unopim-info'       => 'Creant les taules de la base de dades, aquest procés pot trigar uns instants',
                'title'             => 'Instal·lació',
            ],

            'installation-completed' => [
                'admin-panel'                   => 'Panell d’administració',
                'unopim-forums'                 => 'Fòrum UnoPim',
                'explore-unopim-extensions'     => 'Explora extensions d’UnoPim',
                'title-info'                    => 'UnoPim s’ha instal·lat correctament al sistema.',
                'title'                         => 'Instal·lació completada',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Crea la taula de la base de dades',
                'install-info-button'     => 'Fes clic al botó següent per',
                'install-info'            => 'Instal·lar UnoPim',
                'install'                 => 'Instal·la',
                'populate-database-table' => 'Omple les taules de la base de dades',
                'start-installation'      => 'Comença la instal·lació',
                'title'                   => 'Llest per a la instal·lació',
            ],

            'start' => [
                'locale'        => 'Idioma',
                'main'          => 'Començar',
                'select-locale' => 'Selecciona idioma',
                'title'         => 'La teva instal·lació d’UnoPim',
                'welcome-title' => 'Benvingut a UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Calendari',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'FileInfo',
                'filter'      => 'Filtre',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Intl',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 o superior',
                'php'         => 'PHP',
                'session'     => 'Sessió',
                'title'       => 'Requisits del sistema',
                'tokenizer'   => 'Tokenitzador',
                'xml'         => 'XML',
            ],

            'arabic'                    => 'Àrab',
            'back'                      => 'Enrere',
            'unopim-info'               => 'Un projecte comunitari de',
            'unopim-logo'               => 'Logotip UnoPim',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'Bengalí',
            'chinese'                   => 'Xinès',
            'continue'                  => 'Continuar',
            'dutch'                     => 'Neerlandès',
            'english'                   => 'Anglès',
            'french'                    => 'Francès',
            'german'                    => 'Alemany',
            'hebrew'                    => 'Hebreu',
            'hindi'                     => 'Hindi',
            'installation-description'  => 'La instal·lació d’UnoPim normalment consta de diversos passos. Aquí tens un esquema general del procés d’instal·lació:',
            'wizard-language'           => 'Idioma de l’assistent d’instal·lació',
            'installation-info'         => 'Ens alegrem de veure’t aquí!',
            'installation-title'        => 'Benvingut a la instal·lació',
            'italian'                   => 'Italià',
            'japanese'                  => 'Japonès',
            'persian'                   => 'Persa',
            'polish'                    => 'Polonès',
            'portuguese'                => 'Portuguès brasiler',
            'russian'                   => 'Rus',
            'save-configuration'        => 'Desa configuració',
            'sinhala'                   => 'Singalès',
            'skip'                      => 'Saltar',
            'spanish'                   => 'Espanyol',
            'title'                     => 'Instal·lador d’UnoPim',
            'turkish'                   => 'Turc',
            'ukrainian'                 => 'Ucraïnès',
            'webkul'                    => 'Webkul',
        ],
    ],
];
