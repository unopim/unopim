<?php

namespace Webkul\ElasticSearch\Observers;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

class ProductObserver
{
    public function created(Product $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower('products'),
                'id'    => $product->id,
                'body'  => $product->toArray(),
            ]);
        }
    }

    public function updated(Product $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower('products'),
                'id'    => $product->id,
                'body'  => $product->toArray(),
            ]);
        }
    }

    public function deleted(Product $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::delete([
                'index' => strtolower('products'),
                'id'    => $product->id,
            ]);
        }
    }
}
