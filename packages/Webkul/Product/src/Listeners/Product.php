<?php

namespace Webkul\Product\Listeners;

use Illuminate\Support\Facades\Bus;
use Webkul\Product\Jobs\ElasticSearch\DeleteIndex as DeleteElasticSearchIndexJob;
use Webkul\Product\Jobs\ElasticSearch\UpdateCreateIndex as UpdateCreateElasticSearchIndexJob;
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
     * Update or create product indices
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function afterUpdate($product)
    {
        $productIds = $this->getAllRelatedProductIds($product);

        Bus::chain([
            new UpdateCreateElasticSearchIndexJob($productIds),
        ])->dispatch();
    }

    /**
     * Delete product indices
     *
     * @param  int  $productId
     * @return void
     */
    public function beforeDelete($productId)
    {
        if (core()->getConfigData('catalog.products.storefront.search_mode') != 'elastic') {
            return;
        }

        DeleteElasticSearchIndexJob::dispatch([$productId]);
    }

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
