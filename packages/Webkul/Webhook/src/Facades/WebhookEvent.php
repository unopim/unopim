<?php

namespace Webkul\Webhook\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Webhook\Registry\EventRegistry;

/**
 * @method static EventRegistry register(string $entity, array $events)
 * @method static array groups()
 * @method static array keys()
 * @method static bool has(string $event)
 * @method static array forSelect()
 * @method static array forOptions()
 *
 * @see EventRegistry
 */
class WebhookEvent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EventRegistry::class;
    }
}
