<?php

declare(strict_types=1);

namespace Webkul\User\Facades;

use Illuminate\Support\Facades\Facade;

class Bouncer extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'bouncer';
    }
}
