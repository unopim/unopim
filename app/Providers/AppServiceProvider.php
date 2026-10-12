<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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

        ParallelTesting::setUpTestCase($this->rebindWorkerState(...));
    }

    /**
     * Drop the state memoized before the connection switch and give the worker its own storage.
     *
     * Providers boot before the framework switches the connection to the
     * per-worker database, and CoreServiceProvider::boot() reads channels and
     * config during boot, so the Core singleton and the catalog scope memoize
     * models from the main database. Every test would then build requests from
     * main-database channels and locales while validation reads the worker
     * database. The repository cache is primed during that same boot, so it is
     * flushed alongside the memoized singletons.
     *
     * Runtime storage_path() writers — the installer's `storage/installed`
     * marker, job logs, AI temp files — are process-shared state that
     * concurrent workers otherwise race on, so they move to a per-worker
     * directory. Disk roots resolved from config at boot are left untouched.
     */
    protected function rebindWorkerState(int|string $token): void
    {
        $this->app->forgetInstance('core');
        $this->app->forgetInstance(CatalogScope::class);
        Facade::clearResolvedInstance('core');

        Cache::flush();

        $workerStorage = $this->app->basePath('storage/parallel/'.$token);

        foreach (['app', 'logs', 'app/purifier', 'framework/cache', 'framework/sessions', 'framework/testing', 'framework/views'] as $dir) {
            if (! is_dir($workerStorage.'/'.$dir)) {
                mkdir($workerStorage.'/'.$dir, 0777, true);
            }
        }

        $this->app->useStoragePath($workerStorage);
    }
}
