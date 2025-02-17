<?php

namespace Webkul\Product\Factories;

use Webkul\Product\Builders\DatabaseProductQueryBuilder;
use Webkul\Product\Builders\ElasticProductQueryBuilder;

class ProductQueryBuilderFactory
{
    public static function make()
    {
        return core()->isElasticsearchEnabled()
            ? app(ElasticProductQueryBuilder::class)
            : app(DatabaseProductQueryBuilder::class);
    }
}
