<?php

return [
    'views' => [
        [
            'name'    => 'Featured this season',
            'payload' => [
                'filters'             => [['index' => 'is_featured', 'value' => [['operator' => 'eq', 'value' => 1]]]],
                'activeFilterIndices' => ['sku', 'parent', 'attribute_family', 'type', 'categories', 'created_at', 'updated_at', 'is_featured'],
                'columns'             => ['sku', 'name', 'attribute_family', 'is_featured', 'release_date', 'status'],
                'sort'                => ['column' => 'release_date', 'order' => 'desc'],
                'perPage'             => 25,
                'channel'             => 'ecommerce',
                'locale'              => 'en_US',
            ],
        ],
        [
            'name'    => 'Missing German copy',
            'payload' => [
                'filters'             => [['index' => 'completeness', 'value' => [['operator' => 'lt', 'value' => 100]]]],
                'activeFilterIndices' => ['sku', 'parent', 'attribute_family', 'type', 'categories', 'created_at', 'updated_at', 'completeness'],
                'columns'             => ['sku', 'name', 'attribute_family', 'completeness', 'updated_at'],
                'sort'                => ['column' => 'updated_at', 'order' => 'desc'],
                'perPage'             => 50,
                'channel'             => 'ecommerce',
                'locale'              => 'de_DE',
            ],
        ],
        [
            'name'    => 'Repairable range',
            'payload' => [
                'filters'             => [['index' => 'features', 'value' => [['operator' => 'in_list', 'value' => ['repairable']]]]],
                'activeFilterIndices' => ['sku', 'parent', 'attribute_family', 'type', 'categories', 'created_at', 'updated_at', 'features'],
                'columns'             => ['sku', 'name', 'brand', 'material', 'warranty_months', 'status'],
                'sort'                => ['column' => 'sku', 'order' => 'asc'],
                'perPage'             => 25,
                'channel'             => 'default',
                'locale'              => 'en_US',
            ],
        ],
    ],

];
