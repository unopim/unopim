<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Default',
            ],

            'attribute-groups' => [
                'description'      => 'Description',
                'general'          => 'General',
                'meta-description' => 'Meta Description',
                'price'            => 'Price',
                'media'            => 'Media',
            ],

            'attributes' => [
                'brand'                => 'Brand',
                'color'                => 'Color',
                'cost'                 => 'Cost',
                'description'          => 'Description',
                'featured'             => 'Featured',
                'guest-checkout'       => 'Guest Checkout',
                'height'               => 'Height',
                'image'                => 'Image',
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
                'tax-category'         => 'Tax Category',
                'url-key'              => 'URL Key',
                'visible-individually' => 'Visible Individually',
                'weight'               => 'Weight',
                'width'                => 'Width',
            ],

            'attribute-options' => [
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

        'category' => [
            'categories' => [
                'description' => 'Root Category Description',
                'name'        => 'Root',
            ],

            'category_fields' => [
                'name'        => 'Name',
                'description' => 'Description',
            ],
        ],

        'core' => [
            'channels' => [
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

        'user' => [
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
        'middleware' => [
            'already-installed' => 'Application is already installed.',
        ],

        'index' => [
            'create-administrator' => [
                'admin'            => 'Admin',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Confirm Password',
                'email-address'    => 'admin@example.com',
                'email'            => 'Email',
                'password'         => 'Password',
                'title'            => 'Create Administrator',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Allowed Currencies',
                'allowed-locales'     => 'Allowed Locales',
                'application-name'    => 'Application Name',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Chinese Yuan (CNY)',
                'database-connection' => 'Database Connection',
                'database-hostname'   => 'Database Hostname',
                'database-name'       => 'Database Name',
                'database-password'   => 'Database Password',
                'database-port'       => 'Database Port',
                'database-prefix'     => 'Database Prefix',
                'database-username'   => 'Database Username',
                'default-currency'    => 'Default Currency',
                'default-locale'      => 'Default Locale',
                'default-timezone'    => 'Default Timezone',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'Default URL',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Iranian Rial (IRR)',
                'israeli'             => 'Israeli Shekel (AFN)',
                'japanese-yen'        => 'Japanese Yen (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Pound Sterling (GBP)',
                'rupee'               => 'Indian Rupee (INR)',
                'russian-ruble'       => 'Russian Ruble (RUB)',
                'saudi'               => 'Saudi Riyal (SAR)',
                'select-timezone'     => 'Select Timezone',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Database Configuration',
                'turkish-lira'        => 'Turkish Lira (TRY)',
                'ukrainian-hryvnia'   => 'Ukrainian Hryvnia (UAH)',
                'usd'                 => 'US Dollar (USD)',
                'warning-message'     => 'Beware! The settings for your default system languages as well as the default currency are permanent and cannot be changed ever again.',
            ],

            'installation-processing' => [
                'unopim'      => 'Installation UnoPim',
                'unopim-info' => 'Creating the database tables, this can take a few moments',
                'title'       => 'Installation',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Admin Panel',
                'unopim-forums'             => 'UnoPim Forum',
                'explore-unopim-extensions' => 'Explore UnoPim Extension',
                'title-info'                => 'UnoPim is Successfully installed on your system.',
                'title'                     => 'Installation Completed',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Create the database table',
                'install-info-button'     => 'Click the button below to',
                'install-info'            => 'UnoPim For Installation',
                'install'                 => 'Installation',
                'populate-database-table' => 'Populate the database tables',
                'start-installation'      => 'Start Installation',
                'title'                   => 'Ready for Installation',
            ],

            'start' => [
                'locale'        => 'Locale',
                'main'          => 'Start',
                'select-locale' => 'Select Locale',
                'title'         => 'Your UnoPim install',
                'welcome-title' => 'Welcome to UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Calendar',
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
                'openssl'     => 'openssl',
                'pcre'        => 'pcre',
                'pdo'         => 'pdo',
                'php-version' => '8.2 or higher',
                'php'         => 'PHP',
                'session'     => 'session',
                'title'       => 'System Requirements',
                'tokenizer'   => 'tokenizer',
                'xml'         => 'XML',
            ],

            'back'                     => 'Back',
            'unopim-info'              => 'A Community Project by',
            'unopim-logo'              => 'UnoPim Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Continue',
            'installation-description' => 'UnoPim installation typically involves several steps. Here\'s a general outline of the installation process for UnoPim:',
            'wizard-language'          => 'Installation Wizard language',
            'installation-info'        => 'We are happy to see you here!',
            'installation-title'       => 'Welcome to Installation',
            'save-configuration'       => 'Save configuration',
            'skip'                     => 'Skip',
            'title'                    => 'UnoPim Installer',
            'webkul'                   => 'Webkul',
        ],
    ],
];
