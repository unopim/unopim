<?php

namespace Webkul\User\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Webkul\User\Bouncer;
use Webkul\User\Facades\Bouncer as BouncerFacade;
use Webkul\User\Http\Middleware\Bouncer as BouncerMiddleware;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        include __DIR__.'/../Http/helpers.php';

        $this->app['router']->aliasMiddleware('admin', BouncerMiddleware::class);

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        $this->registerBouncer();
    }

    /**
     * Register Bouncer as a singleton.
     */
    protected function registerBouncer(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Bouncer', BouncerFacade::class);

        $this->app->singleton('bouncer', fn () => new Bouncer);
    }
}
