<?php

namespace Webkul\Core\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Prettus\Repository\Events\RepositoryEntityCreated;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Webkul\Core\Listeners\CleanCacheRepository;
use Webkul\Core\Listeners\ResponseCacheHit;

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
        'Spatie\ResponseCache\Events\ResponseCacheHit' => [
            ResponseCacheHit::class,
        ],
    ];
}
