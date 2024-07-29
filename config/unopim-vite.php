<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UnoPim Vite Configuration
    |--------------------------------------------------------------------------
    |
    | Please add your Vite registry here to seamlessly support the `unopim_assets` function.
    |
    */

    'viters' => [
        'admin' => [
            'hot_file'                 => 'admin-default-vite.hot',
            'build_directory'          => 'themes/admin/default/build',
            'package_assets_directory' => 'src/Resources/assets',
        ],

        'installer' => [
            'hot_file'                 => 'installer-default-vite.hot',
            'build_directory'          => 'themes/installer/default/build',
            'package_assets_directory' => 'src/Resources/assets',
        ],
    ],
];
