<?php

declare(strict_types=1);

namespace Webkul\Core\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Prettus\Repository\Events\RepositoryEntityCreated;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Spatie\ResponseCache\Events\ResponseCacheHit;
use Webkul\Core\Listeners\CleanCacheRepository;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        RepositoryEntityCreated::class => [
            CleanCacheRepository::class,
        ],
        RepositoryEntityUpdated::class => [
            CleanCacheRepository::class,
        ],
        RepositoryEntityDeleted::class => [
            CleanCacheRepository::class,
        ],
        ResponseCacheHit::class => [
            \Webkul\Core\Listeners\ResponseCacheHit::class,
        ],
    ];
}
