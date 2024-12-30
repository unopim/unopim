<?php

namespace Webkul\ElasticSearch\Observers;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product as Products;

class Product
{
    public function created(Products $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_products'),
                'id'    => $product->id,
                'body'  => $product->toArray(),
            ]);
        }
    }

    public function updated(Products $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_products'),
                'id'    => $product->id,
                'body'  => $product->toArray(),
            ]);
        }
    }

    public function deleted(Products $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::delete([
                'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_products'),
                'id'    => $product->id,
            ]);
        }
    }
}
