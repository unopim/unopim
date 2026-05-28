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
                    'title'      => 'File Format',
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
                    'title'    => 'With Media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'status',
                    'title'    => 'Status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'Enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'Disable',
                            'value' => 'disable',
                        ],
                    ],
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
                    'title'      => 'File Format',
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
                    'title'    => 'With Media',
                    'required' => false,
                    'type'     => 'boolean',
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
                    'title'      => 'File Format',
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
                    'title'    => 'Status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'Enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'All',
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
                    'title'      => 'File Format',
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
                    'title'      => 'File Format',
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
                    'title'    => 'With Media',
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
