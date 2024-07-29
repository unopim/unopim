<?php

namespace Webkul\Product\Facades;

use Illuminate\Support\Facades\Facade;

class ValueSetter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'value_setter';
    }
}
