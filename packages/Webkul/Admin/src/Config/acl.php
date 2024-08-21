<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | All ACLs related to dashboard will be placed here.
    |
    */
    [
        'key'   => 'dashboard',
        'name'  => 'admin::app.acl.dashboard',
        'route' => 'admin.dashboard.index',
        'sort'  => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    |
    | All ACLs related to catalog will be placed here.
    |
    */
    [
        'key'   => 'catalog',
        'name'  => 'admin::app.acl.catalog',
        'route' => 'admin.catalog.index',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.products',
        'name'  => 'admin::app.acl.products',
        'route' => 'admin.catalog.products.index',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.products.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.catalog.products.store',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.products.copy',
        'name'  => 'admin::app.acl.copy',
        'route' => 'admin.catalog.products.copy',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.products.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.catalog.products.edit',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.products.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.catalog.products.delete',
        'sort'  => 4,
    ], [
        'key'   => 'catalog.categories',
        'name'  => 'admin::app.acl.categories',
        'route' => 'admin.catalog.categories.index',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.categories.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.catalog.categories.create',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.categories.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.catalog.categories.edit',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.categories.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.catalog.categories.delete',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.category_fields',
        'name'  => 'admin::app.acl.category_fields',
        'route' => 'admin.catalog.category_fields.index',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.category_fields.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.catalog.category_fields.create',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.category_fields.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.catalog.category_fields.edit',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.category_fields.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.catalog.category_fields.delete',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.attributes',
        'name'  => 'admin::app.acl.attributes',
        'route' => 'admin.catalog.attributes.index',
        'sort'  => 4,
    ], [
        'key'   => 'catalog.attributes.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.catalog.attributes.create',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.attributes.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.catalog.attributes.edit',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.attributes.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.catalog.attributes.delete',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.attribute_groups',
        'name'  => 'admin::app.acl.attribute-groups',
        'route' => 'admin.catalog.attribute.groups.index',
        'sort'  => 5,
    ], [
        'key'   => 'catalog.attribute_groups.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.catalog.attribute.groups.create',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.attribute_groups.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.catalog.attribute.groups.edit',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.attribute_groups.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.catalog.attribute_groups.delete',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.families',
        'name'  => 'admin::app.acl.attribute-families',
        'route' => 'admin.catalog.families.index',
        'sort'  => 6,
    ], [
        'key'   => 'catalog.families.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.catalog.families.create',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.families.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.catalog.families.edit',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.families.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.catalog.families.delete',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.families.copy',
        'name'  => 'admin::app.acl.copy',
        'route' => 'admin.catalog.families.copy',
        'sort'  => 4,
    ], [
        'key'   => 'history',
        'name'  => 'admin::app.acl.history',
        'route' => 'admin.history.index',
        'sort'  => 10,
    ], [
        'key'   => 'history.view',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.history.view',
        'sort'  => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | All acl related to configuration tabs should be placed here.
    |
    */
    [
        'key'    => 'data_transfer',
        'name'   => 'admin::app.acl.data-transfer',
        'route'  => 'admin.settings.data_transfer.tracker.index',
        'sort'   => 7,
    ], [
        'key'   => 'data_transfer.job_tracker',
        'name'  => 'admin::app.acl.tracker',
        'route' => 'admin.settings.data_transfer.tracker.index',
        'sort'  => 1,
    ], [
        'key'   => 'data_transfer.imports',
        'name'  => 'admin::app.acl.imports',
        'route' => 'admin.settings.data_transfer.imports.index',
        'sort'  => 2,
    ], [
        'key'   => 'data_transfer.imports.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.data_transfer.imports.create',
        'sort'  => 1,
    ], [
        'key'   => 'data_transfer.imports.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.data_transfer.imports.edit',
        'sort'  => 2,
    ], [
        'key'   => 'data_transfer.imports.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.data_transfer.imports.delete',
        'sort'  => 3,
    ], [
        'key'   => 'data_transfer.imports.execute',
        'name'  => 'admin::app.acl.execute',
        'route' => 'admin.settings.data_transfer.imports.import_now',
        'sort'  => 4,
    ], [
        'key'   => 'data_transfer.export',
        'name'  => 'admin::app.acl.exports',
        'route' => 'admin.settings.data_transfer.exports.index',
        'sort'  => 3,
    ], [
        'key'   => 'data_transfer.export.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.data_transfer.exports.create',
        'sort'  => 1,
    ], [
        'key'   => 'data_transfer.export.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.data_transfer.exports.edit',
        'sort'  => 2,
    ], [
        'key'   => 'data_transfer.export.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.data_transfer.exports.delete',
        'sort'  => 3,
    ], [
        'key'   => 'data_transfer.export.execute',
        'name'  => 'admin::app.acl.execute',
        'route' => 'admin.settings.data_transfer.exports.export_now',
        'sort'  => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | All ACLs related to settings will be placed here.
    |
    */
    [
        'key'   => 'settings',
        'name'  => 'admin::app.acl.settings',
        'route' => 'admin.settings.users.index',
        'sort'  => 8,
    ], [
        'key'   => 'settings.locales',
        'name'  => 'admin::app.acl.locales',
        'route' => 'admin.settings.locales.index',
        'sort'  => 1,
    ], [
        'key'   => 'settings.locales.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.locales.create',
        'sort'  => 1,
    ], [
        'key'   => 'settings.locales.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.locales.edit',
        'sort'  => 2,
    ], [
        'key'   => 'settings.locales.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.locales.delete',
        'sort'  => 3,
    ], [
        'key'   => 'settings.currencies',
        'name'  => 'admin::app.acl.currencies',
        'route' => 'admin.settings.currencies.index',
        'sort'  => 2,
    ], [
        'key'   => 'settings.currencies.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.currencies.create',
        'sort'  => 1,
    ], [
        'key'   => 'settings.currencies.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.currencies.edit',
        'sort'  => 2,
    ], [
        'key'   => 'settings.currencies.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.currencies.delete',
        'sort'  => 3,
    ],  [
        'key'   => 'settings.channels',
        'name'  => 'admin::app.acl.channels',
        'route' => 'admin.settings.channels.index',
        'sort'  => 5,
    ], [
        'key'   => 'settings.channels.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.channels.create',
        'sort'  => 1,
    ], [
        'key'   => 'settings.channels.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.channels.edit',
        'sort'  => 2,
    ], [
        'key'   => 'settings.channels.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.channels.delete',
        'sort'  => 3,
    ], [
        'key'   => 'settings.users',
        'name'  => 'admin::app.acl.users',
        'route' => 'admin.settings.users.index',
        'sort'  => 6,
    ], [
        'key'   => 'settings.users.users',
        'name'  => 'admin::app.acl.users',
        'route' => 'admin.settings.users.index',
        'sort'  => 1,
    ], [
        'key'   => 'settings.users.users.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.users.store',
        'sort'  => 1,
    ], [
        'key'   => 'settings.users.users.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.users.edit',
        'sort'  => 2,
    ], [
        'key'   => 'settings.users.users.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.users.delete',
        'sort'  => 3,
    ], [
        'key'   => 'settings.roles',
        'name'  => 'admin::app.acl.roles',
        'route' => 'admin.settings.roles.index',
        'sort'  => 7,
    ], [
        'key'   => 'settings.roles.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.settings.roles.create',
        'sort'  => 1,
    ], [
        'key'   => 'settings.roles.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.settings.roles.edit',
        'sort'  => 2,
    ], [
        'key'   => 'settings.roles.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.settings.roles.delete',
        'sort'  => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | All acl related to configuration tabs should be placed here.
    |
    */
    [
        'key'    => 'configuration',
        'name'   => 'admin::app.acl.configuration',
        'route'  => 'admin.configuration.integrations.index',
        'sort'   => 9,
    ], [
        'key'   => 'configuration.integrations',
        'name'  => 'admin::app.acl.integrations',
        'route' => 'admin.configuration.integrations.index',
        'sort'  => 1,
    ], [
        'key'   => 'configuration.integrations.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.configuration.integrations.create',
        'sort'  => 1,
    ], [
        'key'   => 'configuration.integrations.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.configuration.integrations.edit',
        'sort'  => 2,
    ], [
        'key'   => 'configuration.integrations.delete',
        'name'  => 'admin::app.acl.delete',
        'route' => 'admin.configuration.integrations.delete',
        'sort'  => 3,
    ], [
        'key'    => 'configuration.magic-ai',
        'name'   => 'admin::app.acl.magic-ai',
        'route'  => 'admin.configuration.edit',
        'params' => ['general', 'magic_ai'],
        'sort'   => 2,
    ],
];
