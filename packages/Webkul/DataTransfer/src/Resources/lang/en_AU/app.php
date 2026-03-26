<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Products',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL key: \'%s\' has already been generated for an item with the SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Invalid value for attribute family column (does the attribute family exist?)',
                    'invalid-type'                             => 'Product type is invalid or not supported',
                    'sku-not-found'                            => 'Product with the specified SKU not found',
                    'super-attribute-not-found'                => 'Configurable attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Configurable attributes are required to create a product model',
                    'configurable-attributes-wrong-type'       => 'Only select type attributes that are not locale or channel based are allowed to be configurable attributes for a configurable product',
                    'variant-configurable-attribute-not-found' => 'Variant configurable attribute: :code is required for creating',
                    'not-unique-variant-product'               => 'A product with the same configurable attributes already exists.',
                    'channel-not-exist'                        => 'This channel does not exist.',
                    'locale-not-in-channel'                    => 'This locale is not selected in the channel.',
                    'locale-not-exist'                         => 'This locale does not exist',
                    'not-unique-value'                         => 'The :code value must be unique.',
                    'incorrect-family-for-variant'             => 'The family must be the same as the parent family',
                    'parent-not-exist'                         => 'The parent does not exist.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categories',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'You cannot delete the root category that is associated with a channel',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Products',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL key: \'%s\' has already been generated for an item with the SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Invalid value for attribute family column (does the attribute family exist?)',
                    'invalid-type'              => 'Product type is invalid or not supported',
                    'sku-not-found'             => 'Product with the specified SKU not found',
                    'super-attribute-not-found' => 'Super attribute with code: \'%s\' not found or does not belong to the attribute family: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categories',
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
    'job' => [
        'started'   => 'Job execution started',
        'completed' => 'Job execution completed',
    ],
];
