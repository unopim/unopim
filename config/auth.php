<?php

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
            'model'  => Webkul\User\Models\Admin::class,
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
];
