<?php

declare(strict_types=1);
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Validators\JobInstances\Import\CategoryJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\CurrencyJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator;

return [
    'products' => [
        'title'            => 'data_transfer::app.importers.products.title',
        'importer'         => Importer::class,
        'sample_path'      => 'data-transfer/samples/products.csv',
        'validator'        => ProductJobValidator::class,
        'has_file_options' => true,
    ],

    'categories' => [
        'title'            => 'data_transfer::app.importers.categories.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Category\Importer::class,
        'sample_path'      => 'data-transfer/samples/categories.csv',
        'validator'        => CategoryJobValidator::class,
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
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Currency\Importer::class,
        'sample_path'      => 'data-transfer/samples/currencies.csv',
        'validator'        => CurrencyJobValidator::class,
        'has_file_options' => true,
        'filters'          => [
            'fields' => [
                [
                    'name'       => 'status',
                    'title'      => 'Status',
                    'required'   => false,
                    'type'       => 'select',
                    'options'    => [
                        [
                            'value' => 'enable',
                            'label' => 'Enable',
                        ],
                        [
                            'value' => 'all',
                            'label' => 'All',
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
                    'name'       => 'status',
                    'title'      => 'Status',
                    'required'   => false,
                    'type'       => 'select',
                    'options'    => [
                        [
                            'label' => 'Active',
                            'value' => 'active',
                        ],
                        [
                            'label' => 'All',
                            'value' => 'all',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
