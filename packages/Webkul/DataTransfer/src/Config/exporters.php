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
                [
                    'name'     => 'with_media',
                    'title'    => 'With Media',
                    'required' => false,
                    'type'     => 'boolean',
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
                [
                    'name'     => 'with_media',
                    'title'    => 'With Media',
                    'required' => false,
                    'type'     => 'boolean',
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
                [
                    'name'     => 'with_media',
                    'title'    => 'With Media',
                    'required' => false,
                    'type'     => 'boolean',
                ],
            ],
        ],
    ],
];
