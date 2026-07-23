<?php

return [
    [
        'key'  => 'catalog',
        'name' => 'admin::app.settings.system-settings.system.title',
        'sort' => 2,
    ], [
        'key'  => 'catalog.product_passport',
        'name' => 'passport::app.configuration.product_passport.title',
        'info' => 'passport::app.configuration.product_passport.info',
        'sort' => 1,
    ], [
        'key'    => 'catalog.product_passport.settings',
        'name'   => 'passport::app.configuration.product_passport.settings.title',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'passport::app.configuration.product_passport.settings.enabled',
                'type'          => 'boolean',
                'channel_based' => true,
            ], [
                // Defaults to false: publishing a Digital Product Passport is a
                // deliberate, ten-year legal commitment (see Task 4's
                // immutability guarantees), never an unattended side effect of
                // saving a product. See PassportSettingsTest above.
                'name'  => 'auto_publish',
                'title' => 'passport::app.configuration.product_passport.settings.auto-publish',
                'type'  => 'boolean',
            ], [
                'name'       => 'completeness_threshold',
                'title'      => 'passport::app.configuration.product_passport.settings.completeness-threshold',
                'type'       => 'text',
                'validation' => 'required|integer|min:1|max:100',
            ], [
                'name'  => 'operator_name',
                'title' => 'passport::app.configuration.product_passport.settings.operator-name',
                'type'  => 'text',
            ], [
                'name'  => 'operator_address',
                'title' => 'passport::app.configuration.product_passport.settings.operator-address',
                'type'  => 'textarea',
            ], [
                'name'  => 'operator_eu_rep',
                'title' => 'passport::app.configuration.product_passport.settings.operator-eu-rep',
                'type'  => 'text',
            ], [
                'name'       => 'support_url',
                'title'      => 'passport::app.configuration.product_passport.settings.support-url',
                'type'       => 'text',
                'validation' => 'nullable|url',
            ],
        ],
    ],
];
