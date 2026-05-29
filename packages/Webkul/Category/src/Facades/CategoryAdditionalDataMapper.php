<?php

declare(strict_types=1);

namespace Webkul\Category\Facades;

use Illuminate\Support\Facades\Facade;

class CategoryAdditionalDataMapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'category_additional_data_mapper'; // Matches the binding in the service provider
    }
}
