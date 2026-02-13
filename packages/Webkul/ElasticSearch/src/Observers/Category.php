<?php

namespace Webkul\ElasticSearch\Observers;

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Illuminate\Support\Facades\Log;
use Webkul\Category\Models\Category as Categories;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Traits\ResolveTenantIndex;

class Category
{
    use ResolveTenantIndex;

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

    /**
     * Get the tenant-aware category index name.
     */
    protected function getIndexName(): string
    {
        return $this->tenantAwareIndexName('categories');
    }

    public function created(Categories $category)
    {
        $this->initTenantIndex();

        if (config('elasticsearch.enabled')) {
            try {
                $categoryArray = $category->toArray();
                $categoryArray['tenant_id'] = $category->tenant_id ?? null;

                ElasticSearch::index([
                    'index' => $this->getIndexName(),
                    'id'    => $category->id,
                    'body'  => $categoryArray,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while creating id: '.$category->id.' in '.$this->getIndexName().' index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function updated(Categories $category)
    {
        $this->initTenantIndex();

        if (config('elasticsearch.enabled')) {
            try {
                $categoryArray = $category->toArray();
                $categoryArray['tenant_id'] = $category->tenant_id ?? null;

                ElasticSearch::index([
                    'index' => $this->getIndexName(),
                    'id'    => $category->id,
                    'body'  => $categoryArray,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while updating id: '.$category->id.' in '.$this->getIndexName().' index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Categories $category)
    {
        $this->initTenantIndex();

        if (config('elasticsearch.enabled')) {
            try {
                ElasticSearch::delete([
                    'index' => $this->getIndexName(),
                    'id'    => $category->id,
                ]);
            } catch (ElasticsearchException $e) {
                Log::channel('elasticsearch')->error('Exception while deleting id: '.$category->id.' from '.$this->getIndexName().' index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
