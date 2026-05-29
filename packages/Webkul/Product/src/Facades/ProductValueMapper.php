<?php

declare(strict_types=1);

namespace Webkul\Product\Facades;

use Illuminate\Support\Facades\Facade;

class ProductValueMapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'product_value_mapper'; // Matches the binding in the service provider
    }
}
