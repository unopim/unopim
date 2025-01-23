<?php

namespace Webkul\ElasticSearch\Observers;

use Illuminate\Support\Facades\Log;
use Webkul\Category\Models\Category as Categories;
use Webkul\Core\Facades\ElasticSearch;

class Category
{
    /**
     * Elastic search Index.
     *
     * @var string
     */
    private $indexPrefix;

    public function __construct()
    {
        $this->indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');
    }

    public function created(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            try {
                Elasticsearch::index([
                    'index' => strtolower($this->indexPrefix.'_categories'),
                    'id'    => $category->id,
                    'body'  => $category->toArray(),
                ]);
            } catch (\Exception $e) {
                Log::channel('elasticsearch')->error('Exception while creating id: '.$category->id.' in '.$this->indexPrefix.'_categories index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::channel('elasticsearch')->warning('A category was created while Elasticsearch is disabled. Please enable Elasticsearch and run "php artisan category:index" to index the category.');
        }
    }

    public function updated(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            try {
                Elasticsearch::index([
                    'index' => strtolower($this->indexPrefix.'_categories'),
                    'id'    => $category->id,
                    'body'  => $category->toArray(),
                ]);
            } catch (\Exception $e) {
                Log::channel('elasticsearch')->error('Exception while updating id: '.$category->id.' in '.$this->indexPrefix.'_categories index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::channel('elasticsearch')->warning('A category was updated while Elasticsearch is disabled. Please enable Elasticsearch and run "php artisan category:index" to update the category.');
        }
    }

    public function deleted(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            try {
                Elasticsearch::delete([
                    'index' => strtolower($this->indexPrefix.'_categories'),
                    'id'    => $category->id,
                ]);
            } catch (\Exception $e) {
                Log::channel('elasticsearch')->error('Exception while deleting id: '.$category->id.' from '.$this->indexPrefix.'_categories index: ', [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::channel('elasticsearch')->warning('A category was deleted while Elasticsearch is disabled. Please enable Elasticsearch and run "php artisan category:index" to delete the category.');
        }
    }
}
