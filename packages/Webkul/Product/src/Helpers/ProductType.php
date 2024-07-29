<?php

namespace Webkul\Product\Helpers;

use Webkul\Product\Type\AbstractType;

class ProductType
{
    /**
     * Checks if a ProductType may have variants
     *
     * @param  string  $typeKey  as defined in config('product_types)
     * @return bool whether ProductType is able to have variants
     */
    public static function hasVariants(string $typeKey): bool
    {
        /** @var AbstractType $type */
        $type = app(config('product_types.'.$typeKey.'.class'));

        return $type->hasVariants();
    }

    /**
     * Get all ProductTypes that are allowed to have variants
     *
     * @return array of product_types->keys
     */
    public static function getAllTypesHavingVariants(): array
    {
        $havingVariants = [];

        foreach (config('product_types') as $type) {
            if (self::hasVariants($type['key'])) {
                array_push($havingVariants, $type['key']);
            }
        }

        return $havingVariants;
    }

    /**
     * Checks if a given product type key exists in the configuration.
     *
     * @return bool Returns true if the product type key exists in the configuration,
     *              false otherwise.
     */
    public static function isProductType(string $productType)
    {
        return array_key_exists($productType, config('product_types'));
    }
}
