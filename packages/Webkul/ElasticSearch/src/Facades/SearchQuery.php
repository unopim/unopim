<?php

namespace Webkul\ElasticSearch\Facades;

use Illuminate\Support\Facades\Facade;

class SearchQuery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'search-query-builder';
    }
}
