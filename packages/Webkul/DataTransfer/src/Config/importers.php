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

    'roles' => [
        'title'            => 'data_transfer::app.importers.roles.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Role\Importer',
        'sample_path'      => 'data-transfer/samples/roles.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\RoleJobValidator',
        'has_file_options' => true,
    ],

    'users' => [
        'title'            => 'data_transfer::app.importers.users.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\User\Importer',
        'sample_path'      => 'data-transfer/samples/users.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\UserJobValidator',
        'has_file_options' => true,
        'filters'          => [
            'fields' => [
                [
                    'name'       => 'status',
                    'title'      => 'data_transfer::app.exporters.users.filters.status',
                    'required'   => false,
                    'type'       => 'select',
                    'options'    => [
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
