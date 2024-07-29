<?php

return [
    'importers' => [

        'products' => [
            'title' => 'Products',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL key: \'%s\' was already generated for an item with the SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Invalid value for attribute family column (attribute family doesn\'t exist?)',
                    'invalid-type'                             => 'Product type is invalid or not supported',
                    'sku-not-found'                            => 'Product with specified SKU not found',
                    'super-attribute-not-found'                => 'Configurable attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\'',
                    'configurable-attributes-not-found'        => 'Configurable attributes are required for creating product model',
                    'configurable-attributes-wrong-type'       => 'Only select type attributes are allowed to be configurable attributes for a configurable product',
                    'variant-configurable-attribute-not-found' => 'Variant configurable attribute :code is required for creating',
                    'not-unique-variant-product'               => 'A Product with same configurable attributes already exists.',
                    'channel-not-exist'                        => 'This channel does not exist.',
                    'locale-not-in-channel'                    => 'This locale is not selected in the channel.',
                    'locale-not-exist'                         => 'This locale does not exist',
                    'not-unique-value'                         => 'The :code value must be unique.',
                ],
            ],
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title' => 'Users',
        ],
        'channels' => [
            'title' => 'Channels',
        ],
        'categories' => [
            'title' => 'Categories',
        ],
        'category_field' => [
            'title' => 'Category Field',
        ],
        'attribute' => [
            'title' => 'Attribute',
        ],
        'attribute_group' => [
            'title' => 'Attribute Group',
        ],
        'attribute_family' => [
            'title' => 'Attribute Family',
        ],

    ],

    'exporters' => [

        'products' => [
            'title' => 'Products',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL key: \'%s\' was already generated for an item with the SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Invalid value for attribute family column (attribute family doesn\'t exist?)',
                    'invalid-type'              => 'Product type is invalid or not supported',
                    'sku-not-found'             => 'Product with specified SKU not found',
                    'super-attribute-not-found' => 'Super attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\'',
                ],
            ],
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title' => 'Users',
        ],
        'channels' => [
            'title' => 'Channels',
        ],
        'categories' => [
            'title' => 'Categories',
        ],
        'category_field' => [
            'title' => 'Category Field',
        ],
        'attribute' => [
            'title' => 'Attribute',
        ],
        'attribute_group' => [
            'title' => 'Attribute Group',
        ],
        'attribute_family' => [
            'title' => 'Attribute Family',
        ],

    ],

    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Columns number "%s" have empty headers.',
            'column-name-invalid'  => 'Invalid column names: "%s".',
            'column-not-found'     => 'Required columns not found: %s.',
            'column-numbers'       => 'Number of columns does not correspond to the number of rows in the header.',
            'invalid-attribute'    => 'Header contains invalid attribute(s): "%s".',
            'system'               => 'An unexpected system error occurred.',
            'wrong-quotes'         => 'Curly quotes used instead of straight quotes.',
        ],
    ],
];
