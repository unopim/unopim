<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Admin Credentials
    |--------------------------------------------------------------------------
    |
    | Used by the admin seeder for non-interactive installs (e.g. CI). When
    | empty, the seeder falls back to admin@example.com with a random
    | password.
    |
    */

    'admin' => [
        'email'    => env('INSTALLER_ADMIN_EMAIL'),
        'password' => env('INSTALLER_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Add-On Packages
    |--------------------------------------------------------------------------
    |
    | Add-ons the installer can pull in with Composer and hand over to the
    | package's own install command. Both installers — the CLI command and the
    | web wizard — read this list, so the offer appears in both or in neither.
    |
    | Disabled while the add-ons are brought up to the current module
    | contracts: installing one against this release leaves a broken
    | application, which is worse than not offering it. Set `enabled` back to
    | true once they are compatible.
    |
    */

    'optional_packages' => [
        'enabled' => env('INSTALLER_OPTIONAL_PACKAGES', true),

        'packages' => [
            'dam' => [
                'composer' => 'unopim/dam',
                'label'    => 'Digital Asset Management (DAM)',
                'install'  => 'dam-package:install',
            ],

            'shopify' => [
                'composer' => 'unopim/shopify-connector',
                'label'    => 'Shopify Connector',
                'install'  => 'shopify-package:install',
            ],

            // 'bagisto' => [
            //     'composer' => 'unopim/bagisto-connector',
            //     'label'    => 'Bagisto Connector',
            //     'install'  => 'bagisto-package:install',
            // ],
        ],
    ],
];
