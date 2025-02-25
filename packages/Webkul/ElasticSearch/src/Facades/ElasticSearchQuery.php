<?php

namespace Webkul\ElasticSearch\Facades;

use Illuminate\Support\Facades\Facade;

class ElasticSearchQuery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'elastic-search-query';
    }
}
