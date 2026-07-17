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
];
