<?php

namespace Webkul\Shopify\Helpers;

class ShopifyFields
{
    /**
     * Shopify Mapping Fields.
     *
     * @var array
     */
    public $mappingFields = [
        [
            'name'  => 'title',
            'label' => 'shopify::app.shopify.fields.name',
            'types' => [
                'text',
            ],
            'tooltip' => 'supported attributes types: text',
        ], [
            'name'  => 'descriptionHtml',
            'label' => 'shopify::app.shopify.fields.description',
            'types' => [
                'textarea',
                'text',
            ],
            'tooltip' => 'supported attributes types: text, textarea',
        ], [
            'name'  => 'price',
            'label' => 'shopify::app.shopify.fields.price',
            'types' => [
                'price',
            ],
            'tooltip' => 'supported attributes types: price',
        ], [
            'name'  => 'weight',
            'label' => 'shopify::app.shopify.fields.weight',
            'types' => [
                'number',
                'decimal',
            ],
            'tooltip' => 'supported attributes types: number, metric',
        ], [
            'name'  => 'inventoryQuantity',
            'label' => 'shopify::app.shopify.fields.quantity',
            'types' => [
                'number',
            ],
            'tooltip' => 'supported attributes types: number (Default value will export in case of product creation only**)',
        ], [
            'name'  => 'inventoryTracked',
            'label' => 'shopify::app.shopify.fields.inventory_tracked',
            'types' => [
                'boolean',
            ],
            'tooltip' => 'supported attributes types: boolean',
        ], [
            'name'  => 'inventoryPolicy',
            'label' => 'shopify::app.shopify.fields.allow_purchase_out_of_stock',
            'types' => [
                'boolean',
            ],
            'tooltip' => 'supported attributes types: yes/no',
        ], [
            'name'  => 'vendor',
            'label' => 'shopify::app.shopify.fields.vendor',
            'types' => [
                'text',
                'select',
            ],
            'tooltip' => 'supported attributes types: text, simple select',
        ], [
            'name'  => 'productType',
            'label' => 'shopify::app.shopify.fields.product_type',
            'types' => [
                'text',
                'select',
            ],
            'tooltip' => 'supported attributes types: text, simple select',
        ], [
            'name'  => 'tags',
            'label' => 'shopify::app.shopify.fields.tags',
            'types' => [
                'text',
                'select',
                'textarea',
                'multiselect',
            ],
            'tooltip' => 'supported attributes types: textarea, text, select, multiselect',
        ], [
            'name'  => 'barcode',
            'label' => 'shopify::app.shopify.fields.barcode',
            'types' => [
                'text',
            ],
            'tooltip' => 'supported attributes types: text',

        ], [
            'name'  => 'compareAtPrice',
            'label' => 'shopify::app.shopify.fields.compare_at_price',
            'types' => [
                'price',
            ],
            'tooltip' => 'supported attributes types: price',
        ], [
            'name'  => 'metafields_global_title_tag',
            'label' => 'shopify::app.shopify.fields.seo_title',
            'types' => [
                'textarea',
            ],
            'tooltip' => 'supported attributes types: text',
        ], [
            'name'  => 'metafields_global_description_tag',
            'label' => 'shopify::app.shopify.fields.seo_description',
            'types' => [
                'textarea',
            ],
            'tooltip' => 'supported attributes types: text, textarea',
        ], [
            'name'  => 'handle',
            'label' => 'shopify::app.shopify.fields.handle',
            'types' => [
                'text',
            ],
            'tooltip' => 'supported attributes types: text',
        ], [
            'name'  => 'taxable',
            'label' => 'shopify::app.shopify.fields.taxable',
            'types' => [
                'boolean',
            ],
            'tooltip' => 'supported attributes types: yes/no',
        ], [
            'name'  => 'cost',
            'label' => 'shopify::app.shopify.fields.inventory_cost',
            'types' => [
                'price',
            ],
            'tooltip' => 'supported attributes types: price',
        ],
    ];

    /**
     * Get Shopify mapping fields.
     *
     * @return array The mapping fields for Shopify.
     */
    public function getMappingField()
    {
        return $this->mappingFields;
    }
}
