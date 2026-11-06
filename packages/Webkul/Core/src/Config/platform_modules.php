<?php

return [
    'pim' => [
        'name'           => 'core::app.platform-modules.pim',
        'requires'       => [],
        'route_prefixes' => [
            'admin.catalog.products.',
            'admin.catalog.product.',
            'admin.catalog.bulkedit.',
            'admin.catalog.categories.',
            'admin.catalog.category_fields.',
            'admin.catalog.families.',
            'admin.catalog.association_types.',
            'admin.catalog.passports.',
            'admin.api.products.',
            'admin.api.configurable_products.',
            'admin.api.configrable_products.',
            'admin.api.categories.',
            'admin.api.category-fields',
            'admin.api.families',
            'admin.api.association-types',
            'admin.api.media-files.product',
            'admin.api.media-files.category',
            'admin.api.passports.',
            'admin.dashboard.completeness.',
        ],
    ],
];
