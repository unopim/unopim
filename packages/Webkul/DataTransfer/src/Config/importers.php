<?php

use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Validators\JobInstances\Import\AttributeFamilyJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\AttributeGroupJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\AttributeJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\AttributeOptionJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\CategoryFieldJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\CategoryJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\ChannelJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\CurrencyJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\LocaleJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\RoleJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\UserJobValidator;

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

    'attributes' => [
        'title'            => 'data_transfer::app.importers.attributes.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Attribute\Importer::class,
        'sample_path'      => 'data-transfer/samples/attributes.csv',
        'validator'        => AttributeJobValidator::class,
        'has_file_options' => true,
    ],

    'category-fields' => [
        'title'            => 'data_transfer::app.importers.category-fields.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\CategoryField\Importer::class,
        'sample_path'      => 'data-transfer/samples/category-fields.csv',
        'validator'        => CategoryFieldJobValidator::class,
        'has_file_options' => true,
    ],

    'attribute-groups' => [
        'title'            => 'data_transfer::app.importers.attribute-groups.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\AttributeGroup\Importer::class,
        'sample_path'      => 'data-transfer/samples/attribute-groups.csv',
        'validator'        => AttributeGroupJobValidator::class,
        'has_file_options' => true,
    ],

    'attribute-families' => [
        'title'            => 'data_transfer::app.importers.attribute-families.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer::class,
        'sample_path'      => 'data-transfer/samples/attribute-families.csv',
        'validator'        => AttributeFamilyJobValidator::class,
        'has_file_options' => true,
    ],

    'attribute-options' => [
        'title'            => 'data_transfer::app.importers.attribute-options.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer::class,
        'sample_path'      => 'data-transfer/samples/attribute-options.csv',
        'validator'        => AttributeOptionJobValidator::class,
        'has_file_options' => true,
    ],

    'locales' => [
        'title'            => 'data_transfer::app.importers.locales.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Locale\Importer::class,
        'sample_path'      => 'data-transfer/samples/locales.csv',
        'validator'        => LocaleJobValidator::class,
        'has_file_options' => true,
    ],

    'channels' => [
        'title'            => 'data_transfer::app.importers.channels.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Channel\Importer::class,
        'sample_path'      => 'data-transfer/samples/channels.csv',
        'validator'        => ChannelJobValidator::class,
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
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Role\Importer::class,
        'sample_path'      => 'data-transfer/samples/roles.csv',
        'validator'        => RoleJobValidator::class,
        'has_file_options' => true,
    ],

    'users' => [
        'title'            => 'data_transfer::app.importers.users.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\User\Importer::class,
        'sample_path'      => 'data-transfer/samples/users.csv',
        'validator'        => UserJobValidator::class,
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
