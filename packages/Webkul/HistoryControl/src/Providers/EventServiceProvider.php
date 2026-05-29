<?php

namespace Webkul\HistoryControl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Prettus\Repository\Events\RepositoryEntityCreated;
use Prettus\Repository\Events\RepositoryEntityCreating;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Webkul\HistoryControl\Listeners\History;
use Webkul\HistoryControl\Listeners\ProxyValueSyncEventListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        RepositoryEntityCreating::class => [
            History::class,
        ],
        RepositoryEntityCreated::class => [
            History::class,
        ],
        RepositoryEntityUpdated::class => [
            History::class,
        ],
        RepositoryEntityDeleted::class => [
            History::class,
        ],
    ];

    /**
     * Register the application's event listeners.
     */
    #[\Override]
    public function boot(): void
    {
        $events = app('events');

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }

        Event::listen('core.model.proxy.sync.*', [ProxyValueSyncEventListener::class, 'handle']);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Get the events and handlers.
     */
    #[\Override]
    public function listens(): array
    {
        return $this->listen;
    }
}
