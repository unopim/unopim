<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
     * Seed the per-worker database when the suite runs with `--parallel`.
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
    }
}
