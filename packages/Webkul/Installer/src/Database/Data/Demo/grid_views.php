<?php

return [
    'views' => [
        [
            'name'    => 'Featured this season',
            'payload' => [
                'filters'             => [['index' => 'is_featured', 'value' => '1']],
                'activeFilterIndices' => ['is_featured'],
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
                'filters'             => [['index' => 'completeness', 'value' => 'incomplete']],
                'activeFilterIndices' => ['completeness'],
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
                'filters'             => [['index' => 'features', 'value' => 'repairable']],
                'activeFilterIndices' => ['features'],
                'columns'             => ['sku', 'name', 'brand', 'material', 'warranty_months', 'status'],
                'sort'                => ['column' => 'sku', 'order' => 'asc'],
                'perPage'             => 25,
                'channel'             => 'default',
                'locale'              => 'en_US',
            ],
        ],
    ],

];
