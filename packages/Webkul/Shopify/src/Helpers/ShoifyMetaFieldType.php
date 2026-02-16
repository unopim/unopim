<?php

namespace Webkul\Shopify\Helpers;

class ShoifyMetaFieldType
{
    /**
     * Shopify MetaField Type.
     */
    public array $metaFieldType = [
        'text'    => [
            [
                'id'   => 'single_line_text_field',
                'name' => 'Single line text',
            ],
            [
                'id'   => 'color',
                'name' => 'Color',
            ],
            [
                'id'   => 'rating',
                'name' => 'Rating',
            ],
            [
                'id'   => 'url',
                'name' => 'Url',
            ],
            [
                'id'   => 'multi_line_text_field',
                'name' => 'Multi-line text',
            ],
            [
                'id'   => 'json',
                'name' => 'JSON',
            ],
        ],

        'textarea' => [
            [
                'id'         => 'multi_line_text_field',
                'name'       => 'Multi-line text',
            ],
            [
                'id'         => 'json',
                'name'       => 'JSON',
            ],
        ],

        'boolean' => [
            [
                'id'   => 'boolean',
                'name' => 'True and False',
            ],
            [
                'id'         => 'multi_line_text_field',
                'name'       => 'Multi-line text',
            ],
            [
                'id'   => 'single_line_text_field',
                'name' => 'Single line text',
            ],
        ],

        'select' => [
            [
                'id'         => 'single_line_text_field',
                'name'       => 'Single line text',
            ],
            [
                'id'         => 'multi_line_text_field',
                'name'       => 'Multi-line text',
            ],
        ],

        'multiselect' => [
            [
                'id'         => 'multi_line_text_field',
                'name'       => 'Multi-line text',
            ],
            [
                'id'         => 'single_line_text_field',
                'name'       => 'Single line text',
            ],
        ],

        'date' => [
            [
                'id'         => 'date',
                'name'       => 'Date',
            ],
            [
                'id'         => 'multi_line_text_field',
                'name'       => 'Multi-line text',
            ],
            [
                'id'         => 'single_line_text_field',
                'name'       => 'Single line text',
            ],
        ],

        'decimal' => [
            [
                'id'         => 'number_decimal',
                'name'       => 'Decimal',
            ],
            [
                'id'         => 'number_integer',
                'name'       => 'Number',
            ],
            [
                'id'         => 'dimension',
                'name'       => 'Dimension',
            ],
            [
                'id'         => 'weight',
                'name'       => 'Weight',
            ],
            [
                'id'         => 'volume',
                'name'       => 'Volume',
            ],
            [
                'id'         => 'single_line_text_field',
                'name'       => 'Single line text',
            ],
        ],

        'number' => [
            [
                'id'         => 'number_integer',
                'name'       => 'Number',
            ],
            [
                'id'         => 'number_decimal',
                'name'       => 'Decimal',
            ],
            [
                'id'         => 'dimension',
                'name'       => 'Dimension',
            ],
            [
                'id'         => 'weight',
                'name'       => 'Weight',
            ],
            [
                'id'         => 'volume',
                'name'       => 'Volume',
            ],
            [
                'id'         => 'single_line_text_field',
                'name'       => 'Single line text',
            ],
        ],
    ];

    protected $metaFieldValidation = [
        'single_line_text_field' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum character count',
                'max' => 'Maximum character count',
            ],
            'adminFilterable'          => true,
            'smartCollectionCondition' => true,
        ],

        'color' => [
            'list' => true,
        ],

        'rating' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum rating',
                'max' => 'Maximum rating',
            ],
            'smartCollectionCondition' => true,
            'listvalue'                => [
                'smartCollectionCondition' => true,
            ],

        ],
        'url' => [
            'list' => true,
        ],

        'multi_line_text_field' => [
            'list'       => false,
            'validation' => [
                'min' => 'Minimum character count',
                'max' => 'Maximum character count',
            ],
        ],

        'json' => [
            'list' => false,
        ],

        'boolean' => [
            'list'                     => false,
            'adminFilterable'          => true,
            'smartCollectionCondition' => true,
        ],

        'date' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum date',
                'max' => 'Maximum date',
            ],
        ],

        'number_decimal' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum value',
                'max' => 'Maximum value',
            ],
            'smartCollectionCondition' => true,
            'listvalue'                => [
                'smartCollectionCondition' => true,
            ],

        ],

        'number_integer' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum value',
                'max' => 'Maximum value',
            ],
            'smartCollectionCondition' => true,
            'listvalue'                => [
                'smartCollectionCondition' => true,
            ],
        ],

        'dimension' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum dimension',
                'max' => 'Maximum dimension',
            ],
            'unitoptions' => [
                [
                    'id'   => 'MILLIMETERS',
                    'name' => 'mm',
                ],
                [
                    'id'   => 'CENTIMETERS',
                    'name' => 'cm',
                ],
                [
                    'id'   => 'METERS',
                    'name' => 'm',
                ],
                [
                    'id'   => 'INCHES',
                    'name' => 'in',
                ],
                [
                    'id'   => 'FEET',
                    'name' => 'ft',
                ],
                [
                    'id'   => 'YARDS',
                    'name' => 'yd',
                ],
            ],
        ],

        'volume' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum volume',
                'max' => 'Maximum volume',
            ],
            'unitoptions' => [
                [
                    'id'   => 'MILLILITERS',
                    'name' => 'ml',
                ],
                [
                    'id'   => 'CENTILITERS',
                    'name' => 'cl',
                ],
                [
                    'id'   => 'LITERS',
                    'name' => 'L',
                ],
                [
                    'id'   => 'CUBIC_METERS',
                    'name' => 'm³',
                ],
                [
                    'id'   => 'FLUID_OUNCES',
                    'name' => 'fl oz',
                ],
                [
                    'id'   => 'PINTS',
                    'name' => 'pt',
                ],
                [
                    'id'   => 'QUARTS',
                    'name' => 'qt',
                ],
                [
                    'id'   => 'GALLONS',
                    'name' => 'gal',
                ],
                [
                    'id'   => 'IMPERIAL_FLUID_OUNCES',
                    'name' => 'imp fl oz',
                ],
                [
                    'id'   => 'IMPERIAL_PINTS',
                    'name' => 'imp pt',
                ],
                [
                    'id'   => 'IMPERIAL_QUARTS',
                    'name' => 'imp qt',
                ],
                [
                    'id'   => 'IMPERIAL_GALLONS',
                    'name' => 'imp gal',
                ],
            ],
        ],

        'weight' => [
            'list'       => true,
            'validation' => [
                'min' => 'Minimum weight',
                'max' => 'Maximum weight',
            ],
            'unitoptions' => [
                [
                    'id'   => 'KILOGRAMS',
                    'name' => 'kg',
                ],
                [
                    'id'   => 'GRAMS',
                    'name' => 'g',
                ],
                [
                    'id'   => 'POUNDS',
                    'name' => 'lb',
                ],
                [
                    'id'   => 'OUNCES',
                    'name' => 'oz',
                ],
            ],
        ],
    ];

    /**
     * Get available Shopify API versions.
     *
     * @return array The list of Shopify Metafield Type.
     */
    public function getMetaFieldType(): array
    {
        return [
            'text'    => [
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
                [
                    'id'         => 'color',
                    'name'       => trans('shopify::app.shopify.metafield.type.color'),
                ],
                [
                    'id'         => 'rating',
                    'name'       => trans('shopify::app.shopify.metafield.type.rating'),
                ],
                [
                    'id'         => 'url',
                    'name'       => trans('shopify::app.shopify.metafield.type.url'),
                ],
                [
                    'id'         => 'multi_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.multi_line_text_field'),
                ],
                [
                    'id'         => 'json',
                    'name'       => trans('shopify::app.shopify.metafield.type.json'),
                ],
            ],

            'textarea' => [
                [
                    'id'         => 'multi_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.multi_line_text_field'),
                ],
                [
                    'id'         => 'json',
                    'name'       => trans('shopify::app.shopify.metafield.type.json'),
                ],
            ],

            'boolean' => [
                [
                    'id'         => 'boolean',
                    'name'       => trans('shopify::app.shopify.metafield.type.boolean'),
                ],
                [
                    'id'         => 'multi_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.multi_line_text_field'),
                ],
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
            ],

            'select' => [
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
                [
                    'id'         => 'multi_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.multi_line_text_field'),
                ],
            ],

            'multiselect' => [
                [
                    'id'         => 'multi_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.multi_line_text_field'),
                ],
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
            ],

            'date' => [
                [
                    'id'         => 'date',
                    'name'       => trans('shopify::app.shopify.metafield.type.date'),
                ],
                [
                    'id'         => 'multi_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.multi_line_text_field'),
                ],
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
            ],

            'decimal' => [
                [
                    'id'         => 'number_decimal',
                    'name'       => trans('shopify::app.shopify.metafield.type.number_decimal'),
                ],
                [
                    'id'         => 'number_integer',
                    'name'       => trans('shopify::app.shopify.metafield.type.number_integer'),
                ],
                [
                    'id'         => 'dimension',
                    'name'       => trans('shopify::app.shopify.metafield.type.dimension'),
                ],
                [
                    'id'         => 'weight',
                    'name'       => trans('shopify::app.shopify.metafield.type.weight'),
                ],
                [
                    'id'         => 'volume',
                    'name'       => trans('shopify::app.shopify.metafield.type.volume'),
                ],
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
            ],

            'number' => [
                [
                    'id'         => 'number_integer',
                    'name'       => trans('shopify::app.shopify.metafield.type.number_integer'),
                ],
                [
                    'id'         => 'number_decimal',
                    'name'       => trans('shopify::app.shopify.metafield.type.number_decimal'),
                ],
                [
                    'id'         => 'dimension',
                    'name'       => trans('shopify::app.shopify.metafield.type.dimension'),
                ],
                [
                    'id'         => 'weight',
                    'name'       => trans('shopify::app.shopify.metafield.type.weight'),
                ],
                [
                    'id'         => 'volume',
                    'name'       => trans('shopify::app.shopify.metafield.type.volume'),
                ],
                [
                    'id'         => 'single_line_text_field',
                    'name'       => trans('shopify::app.shopify.metafield.type.single_line_text_field'),
                ],
            ],
        ];
    }

    /**
     * Get available Shopify API versions.
     *
     * @return array The list of Shopify Metafield Type.
     */
    public function getMetaFieldTypeInShopify(): array
    {
        return $this->metaFieldValidation;
    }
}
