<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => 'Pamantayan',
            'attribute-groups'   => [
                'description'      => 'Pagsusuri',
                'general'          => 'Pangkalahatan',
                'inventories'      => 'Ala-ala',
                'meta-description' => 'Meta Paglalarawan',
                'price'            => 'Presyo',
                'technical'        => 'Teknikal',
                'shipping'         => 'Pagpapadala',
            ],
            'attributes' => [
                'brand'                => 'Tatak',
                'color'                => 'Kulay',
                'cost'                 => 'Gastos',
                'description'          => 'Paglalarawan',
                'featured'             => 'Pinakamahalaga',
                'guest-checkout'       => 'Check-out para sa mga bisita',
                'height'               => 'Ta-height',
                'length'               => 'Haba',
                'manage-stock'         => 'Pamahalaan ang imbentaryo',
                'meta-description'     => 'Meta Paglalarawan',
                'meta-keywords'        => 'Meta mga salita',
                'meta-title'           => 'Meta pamagat',
                'name'                 => 'Pangalan',
                'new'                  => 'Bago',
                'price'                => 'Presyo',
                'product-number'       => 'Numero ng produkto',
                'short-description'    => 'Maikling paglalarawan',
                'size'                 => 'Laki',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Special na presyo mula sa',
                'special-price-to'     => 'Special na presyo hanggang sa',
                'special-price'        => 'Special na presyo',
                'status'               => 'Katayuan',
                'tax-category'         => 'Kategorya ng buwis',
                'url-key'              => 'URL key',
                'visible-individually' => 'Nakikita nang paisa-isa',
                'weight'               => 'Timbang',
                'width'                => 'Lapad',
            ],
            'attribute-options' => [
                'black'  => 'Itim',
                'green'  => 'Berde',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Pula',
                's'      => 'S',
                'white'  => 'Puti',
                'xl'     => 'XL',
                'yellow' => 'Dilaw',
            ],
        ],
        'category' => [
            'categories' => [
                'description' => 'Pangunahin kategorya pagsusuri',
                'name'        => 'Pangunahing',
            ],
            'category_fields' => [
                'name'        => 'Pangalan',
                'description' => 'Paglalarawan',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Tungkol sa amin pahina nilalaman',
                    'title'   => 'Tungkol sa amin',
                ],
                'contact-us' => [
                    'content' => 'Makipag-ugnayan sa amin pahina nilalaman',
                    'title'   => 'Makipag-ugnayan sa amin',
                ],
                'customer-service' => [
                    'content' => 'Serbisyo sa kustomer pahina nilalaman',
                    'title'   => 'Serbisyo sa kustomer',
                ],
                'payment-policy' => [
                    'content' => 'Polisiya sa pagbabayad pahina nilalaman',
                    'title'   => 'Polisiya sa pagbabayad',
                ],
                'privacy-policy' => [
                    'content' => 'Patakaran sa privacy pahina nilalaman',
                    'title'   => 'Patakaran sa privacy',
                ],
                'refund-policy' => [
                    'content' => 'Patakaran sa refund pahina nilalaman',
                    'title'   => 'Patakaran sa refund',
                ],
                'return-policy' => [
                    'content' => 'Patakaran sa pagbalik pahina nilalaman',
                    'title'   => 'Patakaran sa pagbalik',
                ],
                'shipping-policy' => [
                    'content' => 'Patakaran sa pagpapadala pahina nilalaman',
                    'title'   => 'Patakaran sa pagpapadala',
                ],
                'terms-conditions' => [
                    'content' => 'Tuntunin pahina nilalaman',
                    'title'   => 'Tuntunin',
                ],
                'terms-of-use' => [
                    'content' => 'Tuntunin ng paggamit pahina nilalaman',
                    'title'   => 'Tuntunin ng paggamit',
                ],
                'whats-new' => [
                    'content' => 'Ano ang bagong pahina nilalaman',
                    'title'   => 'Ano ang bagong',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'meta-title'       => 'Demo store',
                'meta-keywords'    => 'Demo store meta keywords',
                'meta-description' => 'Demo store meta description',
                'name'             => 'Standard',
            ],
            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Afghan afghani',
                'CNY' => 'Chinese yuan',
                'EUR' => 'Euro',
                'GBP' => 'British pound',
                'INR' => 'Indian rupee',
                'IRR' => 'Iranian rial',
                'JPY' => 'Japanese yen',
                'RUB' => 'Russian ruble',
                'SAR' => 'Saudi riyal',
                'TRY' => 'Turkish lira',
                'UAH' => 'Ukrainian hryvnia',
                'USD' => 'United States dollar',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general'   => 'Pangkalahatan',
                'guest'     => 'Bisita',
                'wholesale' => 'Tirahan',
            ],
        ],
        'inventory' => [
            'inventory-sources' => [
                'name' => 'Pamantayan',
            ],
        ],
        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name'    => 'Lahat ng produkto',
                    'options' => [
                        'title' => 'Lahat ng produkto',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Tingnan lahat',
                        'description' => 'Galugarin ang aming mga bagong bold na koleksyon! Itaas ang iyong estilo na may mga draybo na disenyo at mga makulay na kulay. Tuklasin ang mga kamangha-manghang mga disenyo at draybo na kulay na nagdidirekta ng iyong aparador. Maghanda na upang yakapin ang hindi pangkaraniwan!',
                        'title'       => 'Maghanda para sa aming mga bagong bold na koleksyon!',
                    ],
                    'name' => 'Mga draybo na koleksyon',
                ],
                'categories-collections' => [
                    'name' => 'Mga koleksyon ng kategorya',
                ],
                'featured-collections' => [
                    'name'    => 'Mga tampok na koleksyon',
                    'options' => [
                        'title' => 'Mga tampok na produkto',
                    ],
                ],
                'footer-links' => [
                    'name'    => 'Mga link sa ibaba',
                    'options' => [
                        'about-us'         => 'Tungkol sa amin',
                        'contact-us'       => 'Makipag-ugnayan sa amin',
                        'customer-service' => 'Serbisyo sa kustomer',
                        'payment-policy'   => 'Polisiya sa pagbabayad',
                        'privacy-policy'   => 'Patakaran sa privacy',
                        'refund-policy'    => 'Patakaran sa refund',
                        'return-policy'    => 'Patakaran sa pagbalik',
                        'shipping-policy'  => 'Patakaran sa pagpapadala',
                        'terms-conditions' => 'Tuntunin',
                        'terms-of-use'     => 'Tuntunin ng paggamit',
                        'whats-new'        => 'Ano ang bagong',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Mga koleksyon namin',
                        'sub-title-2' => 'Mga koleksyon namin',
                        'title'       => 'Maglaro kasama ang aming mga bagong paglulunsad!',
                    ],
                    'name' => 'Laro container',
                ],
                'image-carousel' => [
                    'name'    => 'Image carousel',
                    'sliders' => [
                        'title' => 'Maghanda para sa isang bagong koleksyon',
                    ],
                ],
                'new-products' => [
                    'name'    => 'Mga bagong produkto',
                    'options' => [
                        'title' => 'Mga bagong produkto',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => 'SIMULAN ANG 40% DISKON SA IYONG UNANG PAGBILI',
                    ],
                    'name' => 'Impormasyon sa alok',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'Pondo ng pagpopondo nang walang dagdag na bayad ay magagamit sa lahat ng pangunahing mga credit card',
                        'free-shipping-info'   => 'Libre ang pagpapadala para sa lahat ng mga order',
                        'product-replace-info' => 'Madaling pagpapalit ng produkto!',
                        'time-support-info'    => 'Dedikadong suporta 24/7 sa pamamagitan ng chat at email',
                    ],
                    'name'  => 'Nilalaman ng serbisyo',
                    'title' => [
                        'emi-available'   => 'EMI Magagamit',
                        'free-shipping'   => 'Libre ang pagpapadala',
                        'product-replace' => 'Pagpapalit ng produkto',
                        'time-support'    => 'Suporta 24/7',
                    ],
                ],
                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Mga koleksyon namin',
                        'sub-title-2' => 'Mga koleksyon namin',
                        'sub-title-3' => 'Mga koleksyon namin',
                        'sub-title-4' => 'Mga koleksyon namin',
                        'sub-title-5' => 'Mga koleksyon namin',
                        'sub-title-6' => 'Mga koleksyon namin',
                        'title'       => 'Maghanda para sa isang laro sa aming mga bagong paglulunsad!',
                    ],
                    'name' => 'Pangunahing koleksyon',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => 'Ang role na ito ay magkakaroon ng lahat ng mga access',
                'name'        => 'Administrator',
            ],
            'users' => [
                'name' => 'Halimbawa',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Tagapangasiwa',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Kumpirmahin ang Password',
                'email-address'    => 'admin@example.com',
                'email'            => 'Email',
                'password'         => 'Password',
                'title'            => 'Lumikha ng Tagapangasiwa',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Mga Pinahihintulutang Pera',
                'allowed-locales'     => 'Mga Pinahihintulutang Lokalisasyon',
                'application-name'    => 'Pangalan ng Aplikasyon',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Yuan ng Tsina (CNY)',
                'database-connection' => 'Koneksyon sa Database',
                'database-hostname'   => 'Pangalan ng Server ng Database',
                'database-name'       => 'Pangalan ng Database',
                'database-password'   => 'Password ng Database',
                'database-port'       => 'Port ng Database',
                'database-prefix'     => 'Prefix ng Database',
                'database-username'   => 'Pangalan ng Gumagamit sa Database',
                'default-currency'    => 'Karaniwang Pera',
                'default-locale'      => 'Karaniwang Lokalisasyon',
                'default-timezone'    => 'Karaniwang Oras ng Lokasyon',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Karaniwang URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iranian Rial (IRR)',
                'israeli'             => 'Israeli Shekel (ILS)',
                'japanese-yen'        => 'Yen ng Hapon (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Pondong Britaniko (GBP)',
                'rupee'               => 'Rupia ng India (INR)',
                'russian-ruble'       => 'Rublo ng Russia (RUB)',
                'saudi'               => 'Riyal ng Saudi (SAR)',
                'select-timezone'     => 'Piliin ang Oras ng Lokasyon',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Koneksyon sa Database',
                'turkish-lira'        => 'Turkish Lira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrainian Hryvnia (UAH)',
                'usd'                 => 'Dollars ng Amerika (USD)',
                'warning-message'     => 'Babala! Hindi na ito mababago pa - Karaniwang Pera at Karaniwang Lokalisasyon.',
            ],

            'installation-processing' => [
                'unopim'      => 'Pag-install ng UnoPim',
                'unopim-info' => 'Ang paglikha ng mga talahanayan sa database ay maaaring tumagal ng ilang minuto.',
                'title'       => 'Proseso ng Pag-install',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Panel ng Administrador',
                'unopim-forums'             => 'Mga Forum ng UnoPim',
                'explore-unopim-extensions' => 'Galugarin ang Mga Extension ng UnoPim',
                'title-info'                => 'Ang UnoPim ay matagumpay na na-install.',
                'title'                     => 'Pag-install Nakumpleto',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Lumikha ng Talahanayan sa Database',
                'install-info-button'     => 'I-click ang pindutang ito upang magsimula',
                'install-info'            => 'ang pag-install ng UnoPim',
                'install'                 => 'I-install',
                'populate-database-table' => 'Punuan ang Talahanayan ng Database',
                'start-installation'      => 'Simulan ang Pag-install',
                'title'                   => 'Handa para sa Pag-install',
            ],

            'start' => [
                'locale'        => 'Lokalisasyon',
                'main'          => 'Pangunahin',
                'select-locale' => 'Piliin ang Wika',
                'title'         => 'Pag-install ng UnoPim',
                'welcome-title' => 'Maligayang pagdating sa UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Kalendaryo',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Fileinfo',
                'filter'      => 'Filter',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Intl',
                'json'        => 'JSON',
                'mbstring'    => 'MBString',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 o mas mataas',
                'php'         => 'PHP',
                'session'     => 'Session',
                'title'       => 'Mga Kinakailangan sa Sistema',
                'tokenizer'   => 'Tokenizer',
                'xml'         => 'XML',
            ],

            'back'                     => 'Bumalik',
            'unopim-info'              => 'Proyektong Komunidad',
            'unopim-logo'              => 'UnoPim Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Magpatuloy',
            'installation-description' => 'Ang pag-install ng UnoPim ay binubuo ng ilang mga hakbang. Narito ang isang pangkalahatang-ideya:',
            'wizard-language'          => 'Wika ng Wizard ng Pag-install',
            'installation-info'        => 'Salamat sa pagdating!',
            'installation-title'       => 'Maligayang pagdating sa Pag-install',
            'save-configuration'       => 'I-save ang Configuration',
            'skip'                     => 'I-skip',
            'title'                    => 'UnoPim Installation Wizard',
            'webkul'                   => 'Webkul',
        ],
    ],
];
