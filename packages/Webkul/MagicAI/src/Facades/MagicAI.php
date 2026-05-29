<?php

declare(strict_types=1);

namespace Webkul\MagicAI\Facades;

use Illuminate\Support\Facades\Facade;

class MagicAI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'magic_ai';
    }
}
