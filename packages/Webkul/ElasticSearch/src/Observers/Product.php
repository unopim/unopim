<?php

namespace Webkul\ElasticSearch\Observers;

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer;
use Webkul\ElasticSearch\Traits\ResolveTenantIndex;
use Webkul\Product\Models\Product as Products;

class Product
{
    use ResolveTenantIndex;
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

    /**
     * Get the tenant-aware product index name.
     */
    protected function getIndexName(): string
    {
        return $this->tenantAwareIndexName('products');
    }

    public function created(Products $product)
    {
        $this->initTenantIndex();

        if (config('elasticsearch.enabled') && self::$isEnabled) {
            $productArray = $product->toArray();

            $productArray['status'] = $productArray['status'] ?? 1;
            $productArray['tenant_id'] = $product->tenant_id ?? null;

            switch (DB::getDriverName()) {
                case 'pgsql':
                    $productArray['status'] = $productArray['status'] == 1 || $productArray['status'] === true;

                    if (isset($productArray['attribute_family']['status'])) {
                        $productArray['attribute_family']['status'] =
                            $productArray['attribute_family']['status'] == 1 || $productArray['attribute_family']['status'] === true;
                    }
                    break;

                case 'mysql':
                default:
                    $productArray['status'] = (int) $productArray['status'];

                    if (isset($productArray['attribute_family']['status'])) {
                        $productArray['attribute_family']['status'] = (int) $productArray['attribute_family']['status'];
                    }
                    break;
            }

            if (isset($productArray['values'])) {
                $productArray['values'] = $this->productIndexingNormalizer->normalize($productArray['values']);
            }

            try {
                ElasticSearch::index([
                    'index' => $this->getIndexName(),
                    'id'    => $product->id,
                    'body'  => $productArray,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error(
                    'Exception while creating id: '.$product->id.' in '.$this->getIndexName().' index: ',
                    ['error' => $e->getMessage()],
                );
            }
        }
    }

    public function updated(Products $product)
    {
        $this->initTenantIndex();

        if (config('elasticsearch.enabled') && self::$isEnabled) {
            try {
                $productArray = $product->toArray();
                $productArray['tenant_id'] = $product->tenant_id ?? null;

                if (isset($productArray['values'])) {
                    $productArray['values'] = $this->productIndexingNormalizer->normalize($productArray['values']);
                }

                ElasticSearch::index([
                    'index' => $this->getIndexName(),
                    'id'    => $product->id,
                    'body'  => $productArray,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while updating id: '.$product->id.' in '.$this->getIndexName().' index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Products $product)
    {
        $this->initTenantIndex();

        if (config('elasticsearch.enabled') && self::$isEnabled) {
            try {
                ElasticSearch::delete([
                    'index' => $this->getIndexName(),
                    'id'    => $product->id,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while deleting id: '.$product->id.' from '.$this->getIndexName().' index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
