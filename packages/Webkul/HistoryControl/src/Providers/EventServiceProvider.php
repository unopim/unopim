<?php

namespace Webkul\HistoryControl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Prettus\Repository\Events\RepositoryEntityCreating' => [
            'Webkul\HistoryControl\Listeners\History',
        ],
        'Prettus\Repository\Events\RepositoryEntityCreated' => [
            'Webkul\HistoryControl\Listeners\History',
        ],
        'Prettus\Repository\Events\RepositoryEntityUpdated' => [
            'Webkul\HistoryControl\Listeners\History',
        ],
        'Prettus\Repository\Events\RepositoryEntityDeleted' => [
            'Webkul\HistoryControl\Listeners\History',
        ],
    ];

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $events = app('events');

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }

        Event::listen('core.model.proxy.sync.*', 'Webkul\HistoryControl\Listeners\ProxyValueSyncEventListener@handle');
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }
}
