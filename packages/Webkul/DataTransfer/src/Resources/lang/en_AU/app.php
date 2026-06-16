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
        'channels' => [
            'title'      => 'Channels',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Channel with code :code not found to delete.',
                    'locale-not-found'         => 'One or more locales do not exist.',
                    'root-category-not-found'  => 'Root category does not exist.',
                    'currency-not-found'       => 'One or more currencies do not exist.',
                    'invalid-locale'           => 'The locale does not exist.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'fields' => [
            'file-format'         => 'File Format',
            'with-media'          => 'With Media',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Enable',
            'all'            => 'All',
        ],
        'products' => [
            'title'              => 'Products',
            'invalid-locales'    => 'The selected locales are not all available for the selected channels.',
            'invalid-currencies' => 'The selected currencies are not all available for the selected channels.',
            'filters'            => [
                'channels'             => 'Channels',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Currencies',
                'currencies-info'      => 'Price attributes are exported per selected currency. Leave empty to export every channel currency.',
                'locales'              => 'Locales',
                'locales-info'         => 'Localizable attributes are exported once per selected locale. Leave empty to export every channel locale.',
                'attributes'           => 'Attributes',
                'attributes-info'      => 'Only the selected attributes are exported. Leave empty to export every attribute in the family.',
                'attribute-families'   => 'Attribute Families',
                'categories'           => 'Categories',
                'completeness'         => 'Completeness',
                'completeness-options' => [
                    'none'         => 'No condition on completeness',
                    'at-least-one' => 'Complete on at least one selected locale',
                    'all'          => 'Complete on all selected locales',
                ],
                'time-condition' => 'Time Condition',
                'time-options'   => [
                    'none'              => 'No date condition',
                    'last-n-days'       => 'Updated products over the last N days',
                    'between-dates'     => 'Updated products between two dates',
                    'since-last-export' => 'Updated products since last export',
                ],
                'time-value'     => 'Number of days',
                'time-date'      => 'Start date',
                'time-date-end'  => 'End date',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Enable',
                    'disable' => 'Disable',
                    'all'     => 'All',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma or space separated SKUs to export, e.g. SKU001, SKU002 SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identifiers',
                'identifiers-info' => 'Paste one SKU / identifier per line to export only those products. Leave empty to export every product.',
            ],
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
        'channels' => [
            'title' => 'Channels',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Job execution started',
        'completed' => 'Job execution completed',
    ],
];
