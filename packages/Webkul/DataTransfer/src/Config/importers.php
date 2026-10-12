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
        'title'              => 'data_transfer::app.importers.products.title',
        'importer'           => Importer::class,
        'sample_path'        => 'data-transfer/samples/import/products.csv',
        'sample_images_path' => 'data-transfer/samples/import/products-with-images.zip',
        'samples'            => [
            'variants' => [
                'path'  => 'data-transfer/samples/import/product-variants.csv',
                'label' => 'data_transfer::app.samples.variants',
            ],
            'multi-locale' => [
                'path'  => 'data-transfer/samples/import/products-multi-locale.csv',
                'label' => 'data_transfer::app.samples.multi-locale',
            ],
            'delete' => [
                'path'  => 'data-transfer/samples/import/products-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => ProductJobValidator::class,
        'has_file_options' => true,
    ],

    'categories' => [
        'title'            => 'data_transfer::app.importers.categories.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Category\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/categories.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/categories-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => CategoryJobValidator::class,
        'has_file_options' => true,
    ],

    'attributes' => [
        'title'            => 'data_transfer::app.importers.attributes.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Attribute\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/attributes.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/attributes-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => AttributeJobValidator::class,
        'has_file_options' => true,
    ],

    'category-fields' => [
        'title'            => 'data_transfer::app.importers.category-fields.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\CategoryField\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/category-fields.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/category-fields-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => CategoryFieldJobValidator::class,
        'has_file_options' => true,
    ],

    'product-associations' => [
        'title'            => 'data_transfer::app.importers.product-associations.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\ProductAssociation\Importer',
        'sample_path'      => 'data-transfer/samples/import/product-associations.csv',
        'samples'          => [
            'custom-fields' => [
                'path'  => 'data-transfer/samples/import/product-associations-custom-fields.csv',
                'label' => 'data_transfer::app.samples.custom-fields',
            ],
            'delete' => [
                'path'  => 'data-transfer/samples/import/product-associations-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\ProductAssociationJobValidator',
        'has_file_options' => true,
    ],

    'attribute-groups' => [
        'title'            => 'data_transfer::app.importers.attribute-groups.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\AttributeGroup\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/attribute-groups.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/attribute-groups-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => AttributeGroupJobValidator::class,
        'has_file_options' => true,
    ],

    'attribute-families' => [
        'title'            => 'data_transfer::app.importers.attribute-families.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/attribute-families.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/attribute-families-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => AttributeFamilyJobValidator::class,
        'has_file_options' => true,
    ],

    'attribute-options' => [
        'title'            => 'data_transfer::app.importers.attribute-options.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/attribute-options.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/attribute-options-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => AttributeOptionJobValidator::class,
        'has_file_options' => true,
    ],

    'locales' => [
        'title'            => 'data_transfer::app.importers.locales.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Locale\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/locales.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/locales-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => LocaleJobValidator::class,
        'has_file_options' => true,
    ],

    'channels' => [
        'title'            => 'data_transfer::app.importers.channels.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Channel\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/channels.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/channels-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => ChannelJobValidator::class,
        'has_file_options' => true,
    ],

    'currencies' => [
        'title'            => 'data_transfer::app.importers.currencies.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\Currency\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/currencies.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/currencies-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
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
        'sample_path'      => 'data-transfer/samples/import/roles.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/roles-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
        'validator'        => RoleJobValidator::class,
        'has_file_options' => true,
    ],

    'users' => [
        'title'            => 'data_transfer::app.importers.users.title',
        'importer'         => Webkul\DataTransfer\Helpers\Importers\User\Importer::class,
        'sample_path'      => 'data-transfer/samples/import/users.csv',
        'samples'          => [
            'delete' => [
                'path'  => 'data-transfer/samples/import/users-delete.csv',
                'label' => 'data_transfer::app.samples.delete',
            ],
        ],
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
