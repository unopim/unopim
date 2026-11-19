<?php

return [
    /**
     * Skip attribute during product copy.
     *
     * Supported Relations: ['categories', 'inventories', 'customer_group_prices', 'images', 'videos', 'product_relations']
     *
     * Support Attributes: All Attributes (Example: 'sku', 'product_number', etc)
     */
    'copy' => [
        'skip_attributes' => [],
    ],

    /**
     * Attribute codes the product grid quick search looks in (text attributes only, max 10).
     */
    'search_fields' => ['sku', 'name'],
];
