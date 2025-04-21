<?php

namespace Webkul\ElasticSearch\Observers;

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Illuminate\Support\Facades\Log;
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

    public function __construct()
    {
        $this->indexPrefix = config('elasticsearch.prefix');
    }

    public function created(Products $product)
    {
        if (config('elasticsearch.enabled')) {
            $productArray = $product->toArray();

            $productArray['status'] = ! isset($productArray['status']) ? 1 : $productArray['status'];

            try {
                ElasticSearch::index([
                    'index' => strtolower($this->indexPrefix.'_products'),
                    'id'    => $product->id,
                    'body'  => $productArray,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while creating id: '.$product->id.' in '.$this->indexPrefix.'_products index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function updated(Products $product)
    {
        if (config('elasticsearch.enabled')) {
            try {
                ElasticSearch::index([
                    'index' => strtolower($this->indexPrefix.'_products'),
                    'id'    => $product->id,
                    'body'  => $product->toArray(),
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while updating id: '.$product->id.' in '.$this->indexPrefix.'_products index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Products $product)
    {
        if (config('elasticsearch.enabled')) {
            try {
                ElasticSearch::delete([
                    'index' => strtolower($this->indexPrefix.'_products'),
                    'id'    => $product->id,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while deleting id: '.$product->id.' from '.$this->indexPrefix.'_products index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
