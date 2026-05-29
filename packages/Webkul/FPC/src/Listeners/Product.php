<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Repositories\ProductBundleOptionProductRepository;
use Webkul\Product\Repositories\ProductGroupedProductRepository;
use Webkul\Product\Repositories\ProductRepository;

class Product
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductBundleOptionProductRepository $productBundleOptionProductRepository,
        protected ProductGroupedProductRepository $productGroupedProductRepository
    ) {}

    /**
     * Update or create product page cache
     */
    public function afterUpdate(ProductContract $product): void
    {
        $urls = $this->getForgettableUrls($product);

        ResponseCache::forget($urls);
    }

    /**
     * Delete product page c
     */
    public function beforeDelete(int $productId): void
    {
        $product = $this->productRepository->find($productId);

        $urls = $this->getForgettableUrls($product);

        ResponseCache::forget($urls);
    }

    /**
     * Returns product urls
     */
    public function getForgettableUrls(ProductContract $product): array
    {
        $urls = [];

        $products = $this->getAllRelatedProducts($product);

        foreach ($products as $product) {
            $urls[] = '/'.$product->url_key;
        }

        return $urls;
    }

    /**
     * Returns parents bundle products associated with simple product
     */
    public function getAllRelatedProducts(ProductContract $product): array
    {
        $products = [$product];

        if ($product->type == 'simple') {
            if ($product->parent_id) {
                $products[] = $product->parent;
            }

            $products = array_merge(
                $products,
                $this->getParentBundleProducts($product),
                $this->getParentGroupProducts($product)
            );
        } elseif ($product->type == 'configurable') {
            $products = [];

            /**
             * Fetching fresh variants.
             */
            foreach ($product->variants()->get() as $variant) {
                $products[] = $variant;
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * Returns parents bundle products associated with simple product
     */
    public function getParentBundleProducts(ProductContract $product): array
    {
        $bundleOptionProducts = $this->productBundleOptionProductRepository->findWhere([
            'product_id' => $product->id,
        ]);

        $products = [];

        foreach ($bundleOptionProducts as $bundleOptionProduct) {
            $products[] = $bundleOptionProduct->bundle_option->product;
        }

        return $products;
    }

    /**
     * Returns parents group products associated with simple product
     */
    public function getParentGroupProducts(ProductContract $product): array
    {
        $groupedOptionProducts = $this->productGroupedProductRepository->findWhere([
            'associated_product_id' => $product->id,
        ]);

        $products = [];

        foreach ($groupedOptionProducts as $groupedOptionProduct) {
            $products[] = $groupedOptionProduct->product;
        }

        return $products;
    }
}
