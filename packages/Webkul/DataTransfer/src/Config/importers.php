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

    'channels' => [
        'title'            => 'data_transfer::app.importers.channels.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Channel\Importer',
        'sample_path'      => 'data-transfer/samples/channels.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\ChannelJobValidator',
        'has_file_options' => true,
    ],
];
