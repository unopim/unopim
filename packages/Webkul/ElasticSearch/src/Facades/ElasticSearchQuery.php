<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch\Facades;

use Illuminate\Support\Facades\Facade;

class ElasticSearchQuery extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'elastic-search-query';
    }
}
