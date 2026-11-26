<?php

namespace Webkul\User\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\ServiceProvider;
use Webkul\User\Bouncer;
use Webkul\User\Facades\Bouncer as BouncerFacade;
use Webkul\User\Http\Middleware\Bouncer as BouncerMiddleware;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * The `admin` group pins each session to the password hash it was opened with,
     * so changing or resetting a password ends that admin's other sessions.
     */
    public function boot(): void
    {
        include __DIR__.'/../Http/helpers.php';

        $this->app['router']->aliasMiddleware('admin', BouncerMiddleware::class);

        $this->app['router']->middlewareGroup('admin', [
            BouncerMiddleware::class,
            AuthenticateSession::class,
        ]);

        AuthenticateSession::redirectUsing(fn (): string => route('admin.session.create'));

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerBouncer();
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerBouncer()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Bouncer', BouncerFacade::class);

        $this->app->singleton('bouncer', fn (): Bouncer => new Bouncer);
    }
}
