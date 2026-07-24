<?php

return [
    [
        'key'   => 'catalog.passport',
        'name'  => 'passport::app.acl.passports.index',
        'route' => 'admin.catalog.passports.index',
        'sort'  => 5,
    ], [
        'key'   => 'catalog.passport.view',
        'name'  => 'passport::app.acl.passports.view',
        'route' => 'admin.catalog.passports.index',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.passport.view',
        'name'  => 'passport::app.acl.passports.view',
        'route' => 'admin.catalog.products.passport.show',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.passport.publish',
        'name'  => 'passport::app.acl.passports.publish',
        'route' => 'admin.catalog.passports.publish',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.passport.withdraw',
        'name'  => 'passport::app.acl.passports.withdraw',
        'route' => 'admin.catalog.passports.withdraw',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.passport.mapping',
        'name'  => 'passport::app.mapping.title',
        'route' => 'admin.catalog.passports.mapping.edit',
        'sort'  => 4,
    ], [
        'key'   => 'catalog.passport.mapping',
        'name'  => 'passport::app.mapping.title',
        'route' => 'admin.catalog.passports.mapping.update',
        'sort'  => 4,
    ], [
        /**
         * Routeless: the Product Passport system-settings row shares the generic
         * editor route, so per-section access is enforced in
         * SystemSettingsController against the hub row's `acl`.
         */
        'key'   => 'configuration.system_settings.product_passport',
        'name'  => 'passport::app.configuration.product_passport.title',
        'route' => null,
        'sort'  => 6,
    ],
];
