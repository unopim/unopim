<?php

namespace Webkul\Product\Listeners;

use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Repositories\ProductRepository;

class Product
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Returns parents bundle product ids associated with simple product
     */
    public function getAllRelatedProductIds(ProductContract $product): array
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
