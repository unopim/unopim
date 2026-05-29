<?php

declare(strict_types=1);

namespace Webkul\Theme\Facades;

use Illuminate\Support\Facades\Facade;

class Themes extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'themes';
    }
}
