<?php

return [
    'shopifyCategories' => [
        'title'     => 'shopify::app.importers.shopify.category',
        'importer'  => 'Webkul\Shopify\Helpers\Importers\Category\Importer',
        'validator' => 'Webkul\Shopify\Validators\JobInstances\Import\ShopifyCategoryAndAttrValidator',
        'filters'   => [
            'fields'  => [
                [
                    'name'       => 'credentials',
                    'title'      => 'shopify::app.shopify.job.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.credential.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'shopify::app.shopify.job.locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.locale.fetch-all',
                ],
            ],
        ],

    ],

    'shopifyAttribute' => [
        'title'     => 'shopify::app.importers.shopify.attribute',
        'importer'  => 'Webkul\Shopify\Helpers\Importers\Attribute\Importer',
        'validator' => 'Webkul\Shopify\Validators\JobInstances\Import\ShopifyCategoryAndAttrValidator',
        'filters'   => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'shopify::app.shopify.job.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.credential.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'shopify::app.shopify.job.locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.locale.fetch-all',
                ],
            ],
        ],
    ],

    'shopifyfamily' => [
        'title'     => 'shopify::app.importers.shopify.family',
        'importer'  => 'Webkul\Shopify\Helpers\Importers\Family\Importer',
        'validator' => 'Webkul\Shopify\Validators\JobInstances\Import\ShopifyFamilyValidator',
        'filters'   => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'shopify::app.shopify.job.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.credential.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'shopify::app.shopify.job.locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.locale.fetch-all',
                ], [
                    'name'       => 'attributegroupid',
                    'title'      => 'shopify::app.shopify.job.attribute-groups',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.attribute-group.fetch-all',
                ],
            ],
        ],

    ],

    'shopifyProduct' => [
        'title'     => 'shopify::app.importers.shopify.product',
        'importer'  => 'Webkul\Shopify\Helpers\Importers\Product\Importer',
        'validator' => 'Webkul\Shopify\Validators\JobInstances\Import\ShopifyProductValidator',
        'filters'   => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'shopify::app.shopify.job.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.credential.fetch-all',
                ], [
                    'name'       => 'channel',
                    'title'      => 'shopify::app.shopify.job.channel',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.channel.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'shopify::app.shopify.job.locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.locale.fetch-all',
                ], [
                    'name'       => 'currency',
                    'title'      => 'shopify::app.shopify.job.currency',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.currency.fetch-all',
                ],
            ],
        ],
    ],

    'shopifyMetaField' => [
        'title'     => 'shopify::app.importers.shopify.metafield',
        'importer'  => 'Webkul\Shopify\Helpers\Importers\Metafield\Importer',
        'validator' => 'Webkul\Shopify\Validators\JobInstances\Import\ShopifyCategoryAndAttrValidator',
        'filters'   => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'shopify::app.shopify.job.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.credential.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'shopify::app.shopify.job.locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.locale.fetch-all',
                ],
            ],
        ],
    ],
];
