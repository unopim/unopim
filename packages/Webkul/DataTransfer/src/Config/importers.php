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

    'locales' => [
        'title'            => 'data_transfer::app.importers.locales.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Locale\Importer',
        'sample_path'      => 'data-transfer/samples/locales.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\LocaleJobValidator',
        'has_file_options' => true,
    ],

    'channels' => [
        'title'            => 'data_transfer::app.importers.channels.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Channel\Importer',
        'sample_path'      => 'data-transfer/samples/channels.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\ChannelJobValidator',
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
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.importers.currencies.filters.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'value' => 'enable',
                            'label' => 'data_transfer::app.importers.currencies.filters.enable',
                        ],
                        [
                            'value' => 'all',
                            'label' => 'data_transfer::app.importers.currencies.filters.all',
                        ],
                    ],
                ],
            ],
        ],
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
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.importers.users.filters.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.importers.users.filters.active',
                            'value' => 'active',
                        ],
                        [
                            'label' => 'data_transfer::app.importers.users.filters.all',
                            'value' => 'all',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
