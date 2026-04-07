<?php

namespace Webkul\Installer\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Installer\Console\Commands\DefaultUser as DefaultUserCommand;
use Webkul\Installer\Console\Commands\Installer as InstallerCommand;
use Webkul\Installer\Console\Commands\PurgeUnusedImages as PurgeUnusedImagesCommand;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\Installer\Http\Middleware\Locale;
use Webkul\Installer\Listeners\Installer;

class InstallerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        $this->app['router']->middlewareGroup('install', [CanInstall::class]);

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'installer');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'installer');

        $this->app['router']->aliasMiddleware('installer_locale', Locale::class);

        Event::listen('unopim.installed', [Installer::class, 'installed']);
    }

    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register the Installer Commands of this package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallerCommand::class,
                DefaultUserCommand::class,
                PurgeUnusedImagesCommand::class,
            ]);
        }
    }
}
