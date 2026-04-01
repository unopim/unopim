<?php

use Webkul\User\Models\Admin;

return [
    'defaults' => [
        'guard'     => 'admin',
        'passwords' => 'admins',
    ],

    'guards' => [
        'admin' => [
            'driver'    => 'session',
            'provider'  => 'admins',
        ],

        'api' => [
            'driver'    => 'passport',
            'provider'  => 'admins',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model'  => Admin::class,
        ],
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table'    => 'admin_password_resets',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | window expires and the user is prompted to re-enter their password via
    | the confirmation screen. By default, the timeout lasts for 3 hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
