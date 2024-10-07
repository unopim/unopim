<?php

namespace Webkul\DataTransfer\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Webkul\DataTransfer\Console\JobExecuteCommand;
use Webkul\DataTransfer\Queue\Worker;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\User\Repositories\AdminRepository;

class DataTransferServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'data_transfer');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/importers.php', 'importers');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/exporters.php', 'exporters');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/actions.php', 'import_settings');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/actions.php', 'export_settings');

        $this->registerWorker();

        $this->registerCommands();
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->singleton('unopim.singlejob.queue.worker', function ($app) {
            $isDownForMaintenance = function () {
                return $this->app->isDownForMaintenance();
            };

            $resetScope = function () use ($app) {
                $app['log']->flushSharedContext();

                if (method_exists($app['log'], 'withoutContext')) {
                    $app['log']->withoutContext();
                }

                if (method_exists($app['db'], 'getConnections')) {
                    foreach ($app['db']->getConnections() as $connection) {
                        $connection->resetTotalQueryDuration();
                        $connection->allowQueryDurationHandlersToRunAgain();
                    }
                }

                $app->forgetScopedInstances();

                Facade::clearResolvedInstances();
            };

            return new Worker(
                $app['queue'],
                $app['events'],
                $app[ExceptionHandler::class],
                $isDownForMaintenance,
                $resetScope
            );
        });
    }

    /**
     * Register the Job Execute Commands of this package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                JobExecuteCommand::class,
            ]);
        }

        $this->app->singleton(JobExecuteCommand::class, function ($app) {
            return new JobExecuteCommand(
                $app['unopim.singlejob.queue.worker'],
                $app['cache.store'],
                $app->make(JobInstancesRepository::class),
                $app->make(JobTrackRepository::class),
                $app->make(AdminRepository::class),
            );
        });
    }
}
