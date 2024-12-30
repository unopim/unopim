<?php

namespace Webkul\ElasticSearch\Observers;

use Webkul\Category\Models\Category as Categories;
use Webkul\Core\Facades\ElasticSearch;

class Category
{
    public function created(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_categories'),
                'id'    => $category->id,
                'body'  => $category->toArray(),
            ]);
        }
    }

    public function updated(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_categories'),
                'id'    => $category->id,
                'body'  => $category->toArray(),
            ]);
        }
    }

    public function deleted(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::delete([
                'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_categories'),
                'id'    => $category->id,
            ]);
        }
    }
}
