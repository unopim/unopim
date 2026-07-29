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
                'name'  => 'enabled',
                'title' => 'passport::app.configuration.product_passport.settings.enabled',
                'type'  => 'boolean',
                'info'  => 'passport::app.configuration.product_passport.settings.enabled-hint',
            ], [
                // Defaults to false: publishing a passport is an immutable legal commitment, never an unattended save side effect.
                'name'  => 'auto_publish',
                'title' => 'passport::app.configuration.product_passport.settings.auto-publish',
                'type'  => 'boolean',
                'info'  => 'passport::app.configuration.product_passport.settings.auto-publish-hint',
            ], [
                'name'        => 'completeness_threshold',
                'title'       => 'passport::app.configuration.product_passport.settings.completeness-threshold',
                'type'        => 'text',
                'validation'  => 'required|integer|min:1|max:100',
                'placeholder' => 'passport::app.configuration.product_passport.settings.completeness-threshold-placeholder',
                'info'        => 'passport::app.configuration.product_passport.settings.completeness-threshold-hint',
            ], [
                'name'        => 'operator_name',
                'title'       => 'passport::app.configuration.product_passport.settings.operator-name',
                'type'        => 'text',
                'placeholder' => 'passport::app.configuration.product_passport.settings.operator-name-placeholder',
                'info'        => 'passport::app.configuration.product_passport.settings.operator-name-hint',
            ], [
                'name'        => 'operator_address',
                'title'       => 'passport::app.configuration.product_passport.settings.operator-address',
                'type'        => 'textarea',
                'placeholder' => 'passport::app.configuration.product_passport.settings.operator-address-placeholder',
                'info'        => 'passport::app.configuration.product_passport.settings.operator-address-hint',
            ], [
                'name'        => 'operator_eu_rep',
                'title'       => 'passport::app.configuration.product_passport.settings.operator-eu-rep',
                'type'        => 'text',
                'placeholder' => 'passport::app.configuration.product_passport.settings.operator-eu-rep-placeholder',
                'info'        => 'passport::app.configuration.product_passport.settings.operator-eu-rep-hint',
            ], [
                'name'        => 'support_url',
                'title'       => 'passport::app.configuration.product_passport.settings.support-url',
                'type'        => 'text',
                'placeholder' => 'passport::app.configuration.product_passport.settings.support-url-placeholder',
                'info'        => 'passport::app.configuration.product_passport.settings.support-url-hint',
            ],
        ],
    ],
];
