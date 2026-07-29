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
        'key'   => 'catalog.passport.view',
        'name'  => 'passport::app.acl.passports.view',
        'route' => 'admin.catalog.passports.versions',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.passport.publish',
        'name'  => 'passport::app.acl.passports.publish',
        'route' => 'admin.catalog.passports.publish',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.passport.publish',
        'name'  => 'passport::app.acl.passports.publish',
        'route' => 'admin.catalog.passports.republish',
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
        'key'   => 'catalog.passport.template.view',
        'name'  => 'passport::app.acl.templates.view',
        'route' => 'admin.catalog.passports.templates.index',
        'sort'  => 5,
    ], [
        'key'   => 'catalog.passport.template.create',
        'name'  => 'passport::app.acl.templates.create',
        'route' => 'admin.catalog.passports.templates.create',
        'sort'  => 6,
    ], [
        'key'   => 'catalog.passport.template.create',
        'name'  => 'passport::app.acl.templates.create',
        'route' => 'admin.catalog.passports.templates.store',
        'sort'  => 6,
    ], [
        'key'   => 'catalog.passport.template.edit',
        'name'  => 'passport::app.acl.templates.edit',
        'route' => 'admin.catalog.passports.templates.edit',
        'sort'  => 7,
    ], [
        'key'   => 'catalog.passport.template.edit',
        'name'  => 'passport::app.acl.templates.edit',
        'route' => 'admin.catalog.passports.templates.update',
        'sort'  => 7,
    ], [
        'key'   => 'catalog.passport.template.delete',
        'name'  => 'passport::app.acl.templates.delete',
        'route' => 'admin.catalog.passports.templates.delete',
        'sort'  => 8,
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
