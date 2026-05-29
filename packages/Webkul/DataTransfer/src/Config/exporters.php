<?php

declare(strict_types=1);
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Validators\JobInstances\Export\CategoryJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\CurrencyJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\ProductJobValidator;
use Webkul\Product\Repositories\ProductRepository;

return [
    'products' => [
        'title'       => 'data_transfer::app.exporters.products.title',
        'exporter'    => Exporter::class,
        'source'      => ProductRepository::class,
        'sample_path' => 'data-transfer/samples/products.csv',
        'validator'   => ProductJobValidator::class,
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
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\Category\Exporter::class,
        'source'      => CategoryRepository::class,
        'sample_path' => 'data-transfer/samples/categories.csv',
        'validator'   => CategoryJobValidator::class,
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
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\Currency\Exporter::class,
        'source'      => CurrencyRepository::class,
        'sample_path' => 'data-transfer/samples/currencies.csv',
        'validator'   => CurrencyJobValidator::class,
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
                            'label' => 'All',
                            'value' => 'all',
                        ], [
                            'label' => 'Enable',
                            'value' => 'enable',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
