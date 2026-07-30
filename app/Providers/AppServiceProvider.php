<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Webkul\Core\CatalogScope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->configureParallelTesting();
    }

    /**
     * Register the named limiters consumed by the `throttle` middleware.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $identifier = $request->user()?->getAuthIdentifier();

            return Limit::perMinute((int) config('app.api_rate_limit'))
                ->by($identifier === null ? 'ip:'.$request->ip() : 'user:'.$identifier);
        });
    }

    /**
     * Seed the per-worker database Laravel creates when the suite runs with `--parallel`.
     */
    protected function configureParallelTesting(): void
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        ParallelTesting::setUpTestDatabase(function (string $database, int $token): void {
            try {
                Artisan::call('db:seed');
            } catch (Throwable $e) {
                Log::error('Parallel test database seeding failed.', [
                    'database'  => $database,
                    'token'     => $token,
                    'exception' => $e,
                ]);
            }
        });

        /*
         * Providers boot before the framework switches the connection to the
         * per-worker database, and CoreServiceProvider::boot() reads channels
         * and config during boot — so the Core singleton and the catalog scope
         * memoize models from the WRONG database. Every test would then build
         * requests from main-database channels/locales while validation reads
         * the worker database. Forget them after the switch so the first
         * core() call re-reads the per-worker database.
         *
         * Runtime storage_path() writers (the installer's `storage/installed`
         * marker, job logs, AI temp files) are also swapped to a per-worker
         * directory — those files are process-shared state that concurrent
         * workers otherwise race on. Disk roots resolved from config at boot
         * are deliberately left untouched.
         */
        ParallelTesting::setUpTestCase(function (int|string $token): void {
            $this->app->forgetInstance('core');
            $this->app->forgetInstance(CatalogScope::class);
            Facade::clearResolvedInstance('core');

            $workerStorage = $this->app->basePath('storage/parallel/'.$token);

            foreach (['app', 'logs', 'app/purifier', 'framework/cache', 'framework/sessions', 'framework/testing', 'framework/views'] as $dir) {
                if (! is_dir($workerStorage.'/'.$dir)) {
                    mkdir($workerStorage.'/'.$dir, 0777, true);
                }
            }

            $this->app->useStoragePath($workerStorage);
        });
    }
}
