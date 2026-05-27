<?php

return [
    'products' => [
        'title'            => 'data_transfer::app.importers.products.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Product\Importer',
        'sample_path'      => 'data-transfer/samples/products.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator',
        'has_file_options' => true,
    ],

    'categories' => [
        'title'            => 'data_transfer::app.importers.categories.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Category\Importer',
        'sample_path'      => 'data-transfer/samples/categories.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\CategoryJobValidator',
        'has_file_options' => true,
    ],
    'currencies' => [
        'title'            => 'data_transfer::app.importers.currencies.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Currency\Importer',
        'sample_path'      => 'data-transfer/samples/currencies.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\CurrencyJobValidator',
        'has_file_options' => true,
        'filters'          => [
            'fields' => [
                [
                    'name'       => 'status',
                    'title'      => 'Status',
                    'type'       => 'select',
                    'required'   => false,
                    'options'    => [
                        ['value' => 'enable', 'label' => 'Enable'],
                        ['value' => 'all', 'label' => 'All'],
                    ],
                ],
            ],
        ],
    ],
];
