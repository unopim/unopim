<?php

return [
    'shopifyProduct' => [
        'title'    => 'shopify::app.exporters.shopify.product',
        'exporter' => 'Webkul\Shopify\Helpers\Exporters\Product\Exporter',
        'source'   => 'Webkul\Product\Repositories\ProductRepository',
        'filters'  => [
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
                    'name'       => 'currency',
                    'title'      => 'shopify::app.shopify.job.currency',
                    'required'   => true,
                    'type'       => 'select',
                    'validation' => 'required',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.currency.fetch-all',
                ], [
                    'name'     => 'productfilter',
                    'title'    => 'shopify::app.shopify.job.productfilter',
                    'required' => false,
                    'type'     => 'textarea',
                ],
            ],
        ],
    ],

    'shopifyCategories' => [
        'title'    => 'shopify::app.exporters.shopify.category',
        'exporter' => 'Webkul\Shopify\Helpers\Exporters\Category\Exporter',
        'source'   => 'Webkul\Category\Repositories\CategoryRepository',
        'filters'  => [
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
                ],
            ],
        ],
    ],

    'shopifyMetafield' => [
        'title'    => 'shopify::app.exporters.shopify.metafields',
        'exporter' => 'Webkul\Shopify\Helpers\Exporters\MetaField\Exporter',
        'source'   => 'Webkul\Shopify\Repositories\ShopifyMetaFieldRepository',
        'filters'  => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'Shopify credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'shopify.credential.fetch-all',
                ],
            ],
        ],
    ],
];
