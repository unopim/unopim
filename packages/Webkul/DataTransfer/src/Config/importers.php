<?php

return [
    'products' => [
        'title'       => 'data_transfer::app.importers.products.title',
        'importer'    => 'Webkul\DataTransfer\Helpers\Importers\Product\Importer',
        'sample_path' => 'data-transfer/samples/products.csv',
    ],
    'categories' => [
        'title'       => 'data_transfer::app.importers.categories.title',
        'importer'    => 'Webkul\DataTransfer\Helpers\Importers\Category\Importer',
        'sample_path' => 'data-transfer/samples/categories.csv',
    ],
];
