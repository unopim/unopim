<?php

declare(strict_types=1);

namespace Webkul\Product\Factories;

use Webkul\ElasticSearch\Contracts\QueryBuilder;
use Webkul\Product\Builders\DatabaseProductQueryBuilder;
use Webkul\Product\Builders\ElasticProductQueryBuilder;

class ProductQueryBuilderFactory
{
    public static function make(): QueryBuilder
    {
        return config('elasticsearch.enabled')
            ? app(ElasticProductQueryBuilder::class)
            : app(DatabaseProductQueryBuilder::class);
    }
}
