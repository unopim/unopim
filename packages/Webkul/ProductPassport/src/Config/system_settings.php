<?php

return [
    /**
     * Top-level "Digital Product Passport" section grouping the Publication and
     * Product Passport rows. A bare section entry (no route/fields/acl) always
     * renders; its rows carry their own `acl`. Registered here because the
     * ProductPassport package owns the DPP feature surface. Ordering is merge-safe:
     * `core()->array_set` deep-merges this section node with its child rows
     * regardless of which config is merged first.
     */
    [
        'key'  => 'digital_product_passport',
        'name' => 'passport::app.configuration.dpp-section.title',
        'info' => 'passport::app.configuration.dpp-section.info',
        'icon' => 'icon-product',
        'sort' => 2,
    ],

    [
        'key'          => 'digital_product_passport.product_passport',
        'name'         => 'passport::app.configuration.product_passport.title',
        'info'         => 'passport::app.configuration.product_passport.info',
        'icon'         => 'icon-setting',
        'config_group' => 'catalog.product_passport.settings',
        'acl'          => 'configuration.system_settings.product_passport',
        'sort'         => 2,
    ],
];
