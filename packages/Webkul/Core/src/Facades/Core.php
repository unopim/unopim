<?php

declare(strict_types=1);

namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Core extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'core';
    }
}
