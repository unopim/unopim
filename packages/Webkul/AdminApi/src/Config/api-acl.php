<?php

return [
    [
        'key'   => 'api',
        'name'  => 'admin::app.acl.api',
        'route' => 'admin.api.channels.index',
        'sort'  => 11,
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
        'key'   => 'api.settings',
        'name'  => 'admin::app.acl.settings',
        'route' => 'admin.api.channels.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.settings.channels',
        'name'  => 'admin::app.acl.channels',
        'route' => 'admin.api.channels.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.settings.locales',
        'name'  => 'admin::app.acl.locales',
        'route' => 'admin.api.locales.index',
        'sort'  => 2,
    ], [
        'key'   => 'api.settings.currencies',
        'name'  => 'admin::app.acl.currencies',
        'route' => 'admin.api.currencies.index',
        'sort'  => 2,
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
        'key'   => 'api.catalog',
        'name'  => 'admin::app.acl.catalog',
        'route' => 'admin.api.products.index',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.products',
        'name'  => 'admin::app.acl.products',
        'route' => 'admin.api.products.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.products.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.products.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.products.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.products.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.products',
        'name'  => 'admin::app.acl.products',
        'route' => 'admin.api.configrable_products.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.products.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.configrable_products.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.products.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.configrable_products.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.categories',
        'name'  => 'admin::app.acl.categories',
        'route' => 'admin.api.categories.index',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.categories.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.categories.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.categories.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.categories.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.category_fields',
        'name'  => 'admin::app.acl.category_fields',
        'route' => 'admin.api.category-fields.index',
        'sort'  => 3,
    ], [
        'key'   => 'api.catalog.category_fields.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.category-fields.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.category_fields.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.category-fields.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.attributes',
        'name'  => 'admin::app.acl.attributes',
        'route' => 'admin.api.attributes.index',
        'sort'  => 4,
    ], [
        'key'   => 'api.catalog.attributes.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.attributes.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.attributes.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.attributes.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.attribute_groups',
        'name'  => 'admin::app.acl.attribute-groups',
        'route' => 'admin.api.attribute_groups.index',
        'sort'  => 5,
    ], [
        'key'   => 'api.catalog.attribute_groups.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.attribute_groups.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.attribute_groups.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.attribute_groups.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.catalog.families',
        'name'  => 'admin::app.acl.attribute-families',
        'route' => 'admin.api.families.index',
        'sort'  => 6,
    ], [
        'key'   => 'api.catalog.families.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.api.families.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.catalog.families.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.api.families.update',
        'sort'  => 2,
    ],
];
