<?php

namespace Webkul\ElasticSearch\Observers;

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


    public function __construct() {
        $this->indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');
    }

    public function created(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower($this->indexPrefix.'_categories'),
                'id'    => $category->id,
                'body'  => $category->toArray(),
            ]);
        }
    }

    public function updated(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower($this->indexPrefix.'_categories'),
                'id'    => $category->id,
                'body'  => $category->toArray(),
            ]);
        }
    }

    public function deleted(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::delete([
                'index' => strtolower($this->indexPrefix.'_categories'),
                'id'    => $category->id,
            ]);
        }
    }
}
