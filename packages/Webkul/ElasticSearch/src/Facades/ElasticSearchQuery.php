<?php

namespace Webkul\ElasticSearch\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @deprecated Resolve and own a Webkul\ElasticSearch\ElasticSearchQuery instance
 *             for each query-building operation.
 */
class ElasticSearchQuery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'elastic-search-query';
    }
}
