<?php

use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Enums\ProductFilter;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Validators\JobInstances\Export\AttributeFamilyJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\AttributeGroupJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\AttributeJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\AttributeOptionJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\CategoryFieldJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\CategoryJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\ChannelJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\CurrencyJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\LocaleJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\ProductJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\RoleJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Export\UserJobValidator;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

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
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'header_row',
                    'title'    => 'data_transfer::app.exporters.fields.header-row',
                    'info'     => 'data_transfer::app.exporters.fields.header-row-info',
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => '1',
                ], [
                    'name'     => 'use_labels',
                    'title'    => 'data_transfer::app.exporters.fields.use-labels',
                    'info'     => 'data_transfer::app.exporters.fields.use-labels-info',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'date_format',
                    'title'    => 'data_transfer::app.exporters.fields.date-format',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'value' => 'Y-m-d',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.yyyy-mm-dd',
                        ], [
                            'value' => 'd-m-Y',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.dd-mm-yyyy',
                        ], [
                            'value' => 'd/m/Y',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.dd-mm-yyyy-slash',
                        ], [
                            'value' => 'm/d/Y',
                            'label' => 'data_transfer::app.exporters.fields.date-format-options.mm-dd-yyyy-slash',
                        ],
                    ],
                ], [
                    'name'        => 'file_path',
                    'title'       => 'data_transfer::app.exporters.fields.file-path',
                    'info'        => 'data_transfer::app.exporters.fields.file-path-info',
                    'required'    => false,
                    'type'        => 'text',
                    'placeholder' => '[code]_[date]',
                ], [
                    'name'       => 'channels',
                    'title'      => 'data_transfer::app.exporters.products.filters.channels',
                    'info'       => 'data_transfer::app.exporters.products.filters.channels-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.channels',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'locales',
                    'title'      => 'data_transfer::app.exporters.products.filters.locales',
                    'info'       => 'data_transfer::app.exporters.products.filters.locales-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.locales',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'depends_on' => ['field' => 'channels', 'as' => 'channels'],
                ], [
                    'name'       => 'currencies',
                    'title'      => 'data_transfer::app.exporters.products.filters.currencies',
                    'info'       => 'data_transfer::app.exporters.products.filters.currencies-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.currencies',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'depends_on' => ['field' => 'channels', 'as' => 'channels'],
                ], [
                    'name'       => 'attributes',
                    'title'      => 'data_transfer::app.exporters.products.filters.attributes',
                    'info'       => 'data_transfer::app.exporters.products.filters.attributes-info',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.attributes',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'attribute_families',
                    'title'      => 'data_transfer::app.exporters.products.filters.attribute-families',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.attribute_families',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'categories',
                    'title'      => 'data_transfer::app.exporters.products.filters.categories',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'list_route' => 'admin.settings.data_transfer.exports.filters.categories',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'     => 'completeness',
                    'title'    => 'data_transfer::app.exporters.products.filters.completeness',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.none',
                            'value' => 'none',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.at-least-one',
                            'value' => 'at_least_one',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.all',
                            'value' => 'all',
                        ],
                    ],
                ], [
                    'name'     => 'time_condition',
                    'title'    => 'data_transfer::app.exporters.products.filters.time-condition',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.none',
                            'value' => 'none',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.last-n-days',
                            'value' => 'last_n_days',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.since-last-export',
                            'value' => 'since_last_export',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.between-dates',
                            'value' => 'between_dates',
                        ],
                    ],
                ], [
                    'name'         => 'time_value',
                    'title'        => 'data_transfer::app.exporters.products.filters.time-value',
                    'required'     => false,
                    'type'         => 'number',
                    'visible_when' => [
                        'field'  => 'time_condition',
                        'values' => ['last_n_days'],
                    ],
                ], [
                    'name'         => 'time_date',
                    'title'        => 'data_transfer::app.exporters.products.filters.time-date',
                    'required'     => false,
                    'type'         => 'date',
                    'visible_when' => [
                        'field'  => 'time_condition',
                        'values' => ['between_dates'],
                    ],
                ], [
                    'name'         => 'time_date_end',
                    'title'        => 'data_transfer::app.exporters.products.filters.time-date-end',
                    'required'     => false,
                    'type'         => 'date',
                    'visible_when' => [
                        'field'  => 'time_condition',
                        'values' => ['between_dates'],
                    ],
                ], [
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.exporters.products.filters.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.products.filters.status-options.enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.status-options.disable',
                            'value' => 'disable',
                        ], [
                            'label' => 'data_transfer::app.exporters.products.filters.status-options.all',
                            'value' => 'all',
                        ],
                    ],
                ], [
                    'name'       => 'sku',
                    'title'      => 'data_transfer::app.exporters.products.filters.identifiers',
                    'info'       => 'data_transfer::app.exporters.products.filters.identifiers-info',
                    'required'   => false,
                    'type'       => 'tags',
                    'full_width' => true,
                ], [
                    'name'         => 'custom_attributes',
                    'required'     => false,
                    'type'         => 'attribute-conditions',
                    'full_width'   => true,
                    'async'        => true,
                    'list_route'   => 'admin.settings.data_transfer.exports.filters.attributes',
                    'query_params' => ['exclude' => [ProductFilter::SKU->value]],
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
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ],
            ],
        ],
    ],

    'category-fields' => [
        'title'       => 'data_transfer::app.exporters.category-fields.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\CategoryField\Exporter::class,
        'source'      => CategoryFieldRepository::class,
        'sample_path' => 'data-transfer/samples/category-fields.csv',
        'validator'   => CategoryFieldJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                ],
            ],
        ],
    ],

    'attributes' => [
        'title'       => 'data_transfer::app.exporters.attributes.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\Attribute\Exporter::class,
        'source'      => AttributeRepository::class,
        'sample_path' => 'data-transfer/samples/attributes.csv',
        'validator'   => AttributeJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                ],
            ],
        ],
    ],

    'attribute-groups' => [
        'title'       => 'data_transfer::app.exporters.attribute-groups.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\AttributeGroup\Exporter::class,
        'source'      => AttributeGroupRepository::class,
        'sample_path' => 'data-transfer/samples/attribute-groups.csv',
        'validator'   => AttributeGroupJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                ],
            ],
        ],
    ],

    'attribute-families' => [
        'title'       => 'data_transfer::app.exporters.attribute-families.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\AttributeFamily\Exporter::class,
        'source'      => AttributeFamilyRepository::class,
        'sample_path' => 'data-transfer/samples/attribute-families.csv',
        'validator'   => AttributeFamilyJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                ],
            ],
        ],
    ],

    'attribute-options' => [
        'title'       => 'data_transfer::app.exporters.attribute-options.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\AttributeOption\Exporter::class,
        'source'      => AttributeOptionRepository::class,
        'sample_path' => 'data-transfer/samples/attribute-options.csv',
        'validator'   => AttributeOptionJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                ],
            ],
        ],
    ],

    'locales' => [
        'title'       => 'data_transfer::app.exporters.locales.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\Locale\Exporter::class,
        'source'      => LocaleRepository::class,
        'sample_path' => 'data-transfer/samples/locales.csv',
        'validator'   => LocaleJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                    'title'    => 'data_transfer::app.exporters.fields.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.fields.enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'data_transfer::app.exporters.fields.all',
                            'value' => 'all',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'channels' => [
        'title'       => 'data_transfer::app.exporters.channels.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\Channel\Exporter::class,
        'source'      => ChannelRepository::class,
        'sample_path' => 'data-transfer/samples/channels.csv',
        'validator'   => ChannelJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                    'title'    => 'data_transfer::app.exporters.fields.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'label' => 'data_transfer::app.exporters.fields.enable',
                            'value' => 'enable',
                        ], [
                            'label' => 'data_transfer::app.exporters.fields.all',
                            'value' => 'all',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'roles' => [
        'title'       => 'data_transfer::app.exporters.roles.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\Role\Exporter::class,
        'source'      => RoleRepository::class,
        'sample_path' => 'data-transfer/samples/roles.csv',
        'validator'   => RoleJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                ],
            ],
        ],
    ],

    'users' => [
        'title'       => 'data_transfer::app.exporters.users.title',
        'exporter'    => Webkul\DataTransfer\Helpers\Exporters\User\Exporter::class,
        'source'      => AdminRepository::class,
        'sample_path' => 'data-transfer/samples/users.csv',
        'validator'   => UserJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
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
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'status',
                    'title'    => 'data_transfer::app.exporters.users.filters.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
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
