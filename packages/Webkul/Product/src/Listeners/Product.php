<?php

namespace Webkul\Product\Listeners;

use Webkul\Product\Repositories\ProductRepository;

class Product
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Returns parents bundle product ids associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    public function getAllRelatedProductIds($product)
    {
        $productIds = [$product->id];

        if ($product->type == 'simple') {
            if ($product->parent_id) {
                $productIds[] = $product->parent_id;
            }

            $productIds = array_merge(
                $productIds,
            );
        } elseif ($product->type == 'configurable') {
            $productIds = [
                ...$product->variants->pluck('id')->toArray(),
                ...$productIds,
            ];
        }

        return $productIds;
    }
}
