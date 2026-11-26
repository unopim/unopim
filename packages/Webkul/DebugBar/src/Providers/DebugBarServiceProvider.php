<?php

namespace Webkul\DebugBar\Providers;

use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Webkul\DebugBar\DataCollector\ModuleCollector;

class DebugBarServiceProvider extends ServiceProvider
{
    /**
     * Register the module collector as a debugbar custom collector.
     *
     * It is handed to the debugbar so it is only built when the debugbar boots; it records every
     * query, so building it in console commands and queue workers would grow memory without bound.
     */
    public function register(): void
    {
        if (! class_exists(LaravelDebugbar::class)) {
            return;
        }

        $this->app['config']->set(
            'debugbar.custom_collectors',
            $this->app['config']->get('debugbar.custom_collectors', []) + [ModuleCollector::class => true]
        );
    }

    /**
     * Feed the module collector from listeners registered once per worker.
     *
     * Registering here instead of in the collector keeps the shared dispatcher from gaining a
     * listener every time a debugbar is built, which the IP debug path does on each request.
     */
    public function boot(Dispatcher $events): void
    {
        if (! class_exists(LaravelDebugbar::class)) {
            return;
        }

        $events->listen('eloquent.retrieved:*', function (string $event, array $models): void {
            $this->activeCollector()?->recordModels($models);
        });

        $events->listen('composing:*', function (string $event, array $payload): void {
            if (($view = $payload[0] ?? null) instanceof View) {
                $this->activeCollector()?->recordView($view);
            }
        });

        $events->listen(function (QueryExecuted $query): void {
            $this->activeCollector()?->recordQuery($query);
        });
    }

    /**
     * Resolve the module collector of the current request's debugbar, if that debugbar is enabled.
     *
     * The current container is read on every call because Octane swaps it for a sandbox per request.
     */
    protected function activeCollector(): ?ModuleCollector
    {
        $container = Container::getInstance();

        if (! $container->resolved(LaravelDebugbar::class)) {
            return null;
        }

        $debugbar = $container->make(LaravelDebugbar::class);

        if (
            ! $debugbar->isEnabled()
            || ! $debugbar->hasCollector('modules')
        ) {
            return null;
        }

        $collector = $debugbar->getCollector('modules');

        return $collector instanceof ModuleCollector ? $collector : null;
    }
}
