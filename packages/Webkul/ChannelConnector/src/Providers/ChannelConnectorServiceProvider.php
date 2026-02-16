<?php

namespace Webkul\ChannelConnector\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ChannelConnectorServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\ChannelConnector\Models\ChannelConnector::class,
        \Webkul\ChannelConnector\Models\ChannelFieldMapping::class,
        \Webkul\ChannelConnector\Models\ChannelSyncJob::class,
        \Webkul\ChannelConnector\Models\ProductChannelMapping::class,
        \Webkul\ChannelConnector\Models\ChannelSyncConflict::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(\Webkul\ChannelConnector\Services\AdapterResolver::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/api-acl.php', 'api-acl');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'channel_connector');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadRoutesFrom(__DIR__.'/../Routes/api-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'channel_connector');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Webkul\ChannelConnector\Console\Commands\RunScheduledSyncsCommand::class,
            ]);
        }
    }
}
