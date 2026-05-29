<?php

declare(strict_types=1);

namespace Webkul\Product\Facades;

use Illuminate\Support\Facades\Facade;

class ProductImage extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'product_image';
    }
}
