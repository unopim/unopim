<?php

namespace Webkul\Product\Facades;

use Illuminate\Support\Facades\Facade;

class ProductValueMapper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'product_value_mapper'; // Matches the binding in the service provider
    }
}
