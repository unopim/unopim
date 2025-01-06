<?php

namespace Webkul\ElasticSearch\Observers;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product as Products;

class Product
{
    /**
     * Elastic search Index.
     *
     * @var string
     */
    private $indexPrefix;


    public function __construct() {
        $this->indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');
    }

    public function created(Products $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower($this->indexPrefix.'_products'),
                'id'    => $product->id,
                'body'  => $product->toArray(),
            ]);
        }
    }

    public function updated(Products $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower($this->indexPrefix.'_products'),
                'id'    => $product->id,
                'body'  => $product->toArray(),
            ]);
        }
    }

    public function deleted(Products $product)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::delete([
                'index' => strtolower($this->indexPrefix.'_products'),
                'id'    => $product->id,
            ]);
        }
    }
}
