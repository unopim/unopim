<?php

return [
    'products' => [
        'title'       => 'data_transfer::app.exporters.products.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\Product\Exporter',
        'source'      => 'Webkul\Product\Repositories\ProductRepository',
        'sample_path' => 'data-transfer/samples/products.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\ProductJobValidator',
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ], [
                    'name'     => 'with_media',
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'header_row',
                    'title'    => 'data_transfer::app.exporters.fields.header-row',
                    'info'     => 'data_transfer::app.exporters.fields.header-row-info',
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => '1',
                ], [
                    'name'     => 'use_labels',
                    'title'    => 'data_transfer::app.exporters.fields.use-labels',
                    'info'     => 'data_transfer::app.exporters.fields.use-labels-info',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'date_format',
                    'title'    => 'data_transfer::app.exporters.fields.date-format',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'value' => 'Y-m-d',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.yyyy-mm-dd',
                        ], [
                            'value' => 'd-m-Y',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.dd-mm-yyyy',
                        ], [
                            'value' => 'd/m/Y',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.dd-mm-yyyy-slash',
                        ], [
                            'value' => 'm/d/Y',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.mm-dd-yyyy-slash',
                        ],
                    ],
                ], [
                    'name'        => 'file_path',
                    'title'       => 'data_transfer::app.exporters.fields.file-path',
                    'info'        => 'data_transfer::app.exporters.fields.file-path-info',
                    'required'    => false,
                    'type'        => 'text',
                    'placeholder' => '[code]_[date]',
                ], [
                    'name'       => 'channels',
                    'title'      => 'data_transfer::app.exporters.products.filters.channels',
                    'info'       => 'data_transfer::app.exporters.products.filters.channels-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.channels',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'locales',
                    'title'      => 'data_transfer::app.exporters.products.filters.locales',
                    'info'       => 'data_transfer::app.exporters.products.filters.locales-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.locales',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'currencies',
                    'title'      => 'data_transfer::app.exporters.products.filters.currencies',
                    'info'       => 'data_transfer::app.exporters.products.filters.currencies-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.currencies',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'attributes',
                    'title'      => 'data_transfer::app.exporters.products.filters.attributes',
                    'info'       => 'data_transfer::app.exporters.products.filters.attributes-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.attributes',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'attribute_families',
                    'title'      => 'data_transfer::app.exporters.products.filters.attribute-families',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.attribute_families',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'categories',
                    'title'      => 'data_transfer::app.exporters.products.filters.categories',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.categories',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'     => 'completeness',
                    'title'    => 'data_transfer::app.exporters.products.filters.completeness',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.none',
                            'value' => 'none',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.at-least-one',
                            'value' => 'at_least_one',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.all',
                            'value' => 'all',
                        ],
                    ],
                ], [
                    'name'     => 'time_condition',
                    'title'    => 'data_transfer::app.exporters.products.filters.time-condition',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.none',
                            'value' => 'none',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.last-n-days',
                            'value' => 'last_n_days',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.since-last-export',
                            'value' => 'since_last_export',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.between-dates',
                            'value' => 'between_dates',
                        ],
                    ],
                ], [
                    'name'         => 'time_value',
                    'title'        => 'data_transfer::app.exporters.products.filters.time-value',
                    'required'     => false,
                    'type'         => 'number',
                    'visible_when' => [
                        'field'  => 'time_condition',
                        'values' => ['last_n_days'],
                    ],
                ], [
                    'name'         => 'time_date',
                    'title'        => 'data_transfer::app.exporters.products.filters.time-date',
                    'required'     => false,
                    'type'         => 'date',
                    'visible_when' => [
                        'field'  => 'time_condition',
                        'values' => ['between_dates'],
                    ],
                ], [
                    'name'         => 'time_date_end',
                    'title'        => 'data_transfer::app.exporters.products.filters.time-date-end',
                    'required'     => false,
                    'type'         => 'date',
                    'visible_when' => [
                        'field'  => 'time_condition',
                        'values' => ['between_dates'],
                    ],
                ], [
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.exporters.products.filters.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.products.filters.status-options.enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.status-options.disable',
                            'value' => 'disable',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.status-options.all',
                            'value' => 'all',
                        ],
                    ],
                ], [
                    'name'       => 'sku',
                    'title'      => 'data_transfer::app.exporters.products.filters.identifiers',
                    'info'       => 'data_transfer::app.exporters.products.filters.identifiers-info',
                    'required'   => false,
                    'type'       => 'tags',
                    'full_width' => true,
                ],
            ],
        ],
    ],

    'categories' => [
        'title'       => 'data_transfer::app.exporters.categories.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\Category\Exporter',
        'source'      => 'Webkul\Category\Repositories\CategoryRepository',
        'sample_path' => 'data-transfer/samples/categories.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\CategoryJobValidator',
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ], [
                    'name'     => 'with_media',
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ],
            ],
        ],
    ],

    'channels' => [
        'title'       => 'data_transfer::app.exporters.channels.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\Channel\Exporter',
        'source'      => 'Webkul\Core\Repositories\ChannelRepository',
        'sample_path' => 'data-transfer/samples/channels.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\ChannelJobValidator',
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'currencies' => [
        'title'       => 'data_transfer::app.exporters.currencies.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\Currency\Exporter',
        'source'      => 'Webkul\Core\Repositories\CurrencyRepository',
        'sample_path' => 'data-transfer/samples/currencies.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\CurrencyJobValidator',
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ], [
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.exporters.fields.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.fields.enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'data_transfer::app.exporters.fields.all',
                            'value' => 'all',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'roles' => [
        'title'       => 'data_transfer::app.exporters.roles.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\Role\Exporter',
        'source'      => 'Webkul\User\Repositories\RoleRepository',
        'sample_path' => 'data-transfer/samples/roles.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\RoleJobValidator',
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'users' => [
        'title'       => 'data_transfer::app.exporters.users.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\User\Exporter',
        'source'      => 'Webkul\User\Repositories\AdminRepository',
        'sample_path' => 'data-transfer/samples/users.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\UserJobValidator',
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ], [
                    'name'     => 'with_media',
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.exporters.users.filters.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.users.filters.active',
                            'value' => 'active',
                        ], [
                            'label' => 'data_transfer::app.exporters.users.filters.all',
                            'value' => 'all',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
