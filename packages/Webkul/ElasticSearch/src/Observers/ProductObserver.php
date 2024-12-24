<?php

namespace Webkul\ElasticSearch\Observers;

use Webkul\ElasticSearch\Services\ElasticsearchService;
use Webkul\Product\Models\Product;

class ProductObserver
{
    protected $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    public function created(Product $product)
    {
        $this->elasticsearchService->index(strtolower('products_'.core()->getRequestedChannelCode().'_'.core()->getRequestedLocaleCode().'_index'), $product->id, $product->toArray());
    }

    public function updated(Product $product)
    {
        $this->elasticsearchService->index(strtolower('products_'.core()->getRequestedChannelCode().'_'.core()->getRequestedLocaleCode().'_index'), $product->id, $product->toArray());
    }

    public function deleted(Product $product)
    {
        $this->elasticsearchService->delete(strtolower('products_'.core()->getRequestedChannelCode().'_'.core()->getRequestedLocaleCode().'_index'), $product->id);
    }
}
