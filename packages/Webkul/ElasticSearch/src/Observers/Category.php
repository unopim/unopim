<?php

namespace Webkul\ElasticSearch\Observers;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\Category\Models\Category as Categories;

class Category
{
    public function created(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower('categories'),
                'id'    => $category->id,
                'body'  => $category->toArray(),
            ]);
        }
    }

    public function updated(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::index([
                'index' => strtolower('categories'),
                'id'    => $category->id,
                'body'  => $category->toArray(),
            ]);
        }
    }

    public function deleted(Categories $category)
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            Elasticsearch::delete([
                'index' => strtolower('categories'),
                'id'    => $category->id,
            ]);
        }
    }
}
