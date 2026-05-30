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

    'attributes' => [
        'title'            => 'data_transfer::app.importers.attributes.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Attribute\Importer',
        'sample_path'      => 'data-transfer/samples/attributes.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeJobValidator',
        'has_file_options' => true,
    ],

    'category-fields' => [
        'title'            => 'data_transfer::app.importers.category-fields.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\CategoryField\Importer',
        'sample_path'      => 'data-transfer/samples/category-fields.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\CategoryFieldJobValidator',
        'has_file_options' => true,
    ],

    'attribute-groups' => [
        'title'            => 'data_transfer::app.importers.attribute-groups.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\AttributeGroup\Importer',
        'sample_path'      => 'data-transfer/samples/attribute-groups.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeGroupJobValidator',
        'has_file_options' => true,
    ],

    'attribute-families' => [
        'title'            => 'data_transfer::app.importers.attribute-families.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer',
        'sample_path'      => 'data-transfer/samples/attribute-families.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeFamilyJobValidator',
        'has_file_options' => true,
    ],

    'attribute-options' => [
        'title'            => 'data_transfer::app.importers.attribute-options.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer',
        'sample_path'      => 'data-transfer/samples/attribute-options.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeOptionJobValidator',
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
