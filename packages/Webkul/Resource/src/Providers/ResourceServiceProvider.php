<?php

namespace Webkul\Resource\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Resource\Support\ResourceRegistry;

class ResourceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResourceRegistry::class, fn ($app): ResourceRegistry => new ResourceRegistry($app));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'resource');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'resource');
    }
}
