<?php

declare(strict_types=1);

namespace Webkul\Product\Facades;

use Illuminate\Support\Facades\Facade;

class ValueSetter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'value_setter';
    }
}
