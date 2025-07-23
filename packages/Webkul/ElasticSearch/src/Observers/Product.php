<?php

namespace Webkul\ElasticSearch\Observers;

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer;
use Webkul\Product\Models\Product as Products;

class Product
{
    /**
     * bool flag to manage observer functionality
     */
    protected static bool $isEnabled = true;

    /**
     * Enable the observer functionality.
     */
    public static function enable(): void
    {
        self::$isEnabled = true;
    }

    /**
     * Disable the observer functionality.
     */
    public static function disable(): void
    {
        self::$isEnabled = false;
    }

    /**
     * Get the current state of the observer functionality.
     */
    public static function isEnabled(): bool
    {
        return self::$isEnabled;
    }

    /**
     * Elastic search Index.
     *
     * @var string
     */
    private $indexPrefix;

    public function __construct(protected ProductNormalizer $productIndexingNormalizer)
    {
        $this->indexPrefix = config('elasticsearch.prefix');
    }

    public function created(Products $product)
    {
        if (config('elasticsearch.enabled') && self::$isEnabled) {
            $productArray = $product->toArray();

            $productArray['status'] = ! isset($productArray['status']) ? 1 : $productArray['status'];

            if (isset($productArray['values'])) {
                $productArray['values'] = $this->productIndexingNormalizer->normalize($productArray['values']);
            }

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
        if (config('elasticsearch.enabled') && self::$isEnabled) {
            try {
                $productArray = $product->toArray();

                if (isset($productArray['values'])) {
                    $productArray['values'] = $this->productIndexingNormalizer->normalize($productArray['values']);
                }

                ElasticSearch::index([
                    'index' => strtolower($this->indexPrefix.'_products'),
                    'id'    => $product->id,
                    'body'  => $productArray,
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
        if (config('elasticsearch.enabled') && self::$isEnabled) {
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
