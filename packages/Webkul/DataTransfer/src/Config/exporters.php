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

    'category-fields' => [
        'title'       => 'data_transfer::app.exporters.category-fields.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\CategoryField\Exporter',
        'source'      => 'Webkul\Category\Repositories\CategoryFieldRepository',
        'sample_path' => 'data-transfer/samples/category-fields.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\CategoryFieldJobValidator',
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

    'attributes' => [
        'title'       => 'data_transfer::app.exporters.attributes.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\Attribute\Exporter',
        'source'      => 'Webkul\Attribute\Repositories\AttributeRepository',
        'sample_path' => 'data-transfer/samples/attributes.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\AttributeJobValidator',
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

    'attribute-groups' => [
        'title'       => 'data_transfer::app.exporters.attribute-groups.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\AttributeGroup\Exporter',
        'source'      => 'Webkul\Attribute\Repositories\AttributeGroupRepository',
        'sample_path' => 'data-transfer/samples/attribute-groups.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\AttributeGroupJobValidator',
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

    'attribute-families' => [
        'title'       => 'data_transfer::app.exporters.attribute-families.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\AttributeFamily\Exporter',
        'source'      => 'Webkul\Attribute\Repositories\AttributeFamilyRepository',
        'sample_path' => 'data-transfer/samples/attribute-families.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\AttributeFamilyJobValidator',
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

    'attribute-options' => [
        'title'       => 'data_transfer::app.exporters.attribute-options.title',
        'exporter'    => 'Webkul\DataTransfer\Helpers\Exporters\AttributeOption\Exporter',
        'source'      => 'Webkul\Attribute\Repositories\AttributeOptionRepository',
        'sample_path' => 'data-transfer/samples/attribute-options.csv',
        'validator'   => 'Webkul\DataTransfer\Validators\JobInstances\Export\AttributeOptionJobValidator',
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
