<?php

namespace Webkul\Webhook\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Webhook\Console\Commands\PruneWebhookLogs;
use Webkul\Webhook\Registry\EventRegistry;

class WebhookServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'webhook');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'webhook');

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneWebhookLogs::class]);

            $this->app->booted(function (): void {
                $this->app->make(Schedule::class)->command('webhook:logs:prune')->daily();
            });
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(EventRegistry::class, fn (): EventRegistry => new EventRegistry(
            config('webhook.events', [])
        ));
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php', 'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/webhook.php', 'webhook'
        );
    }
}
