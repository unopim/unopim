<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            try {
                Artisan::call('db:seed');
            } catch (\Throwable $e) {
                logger()->error("Parallel DB seed failed for {$database}: ".$e->getMessage());
            }
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configureDebugbar();
    }

    /**
     * Conditionally disable debugbar based on allowed IPs.
     */
    protected function configureDebugbar(): void
    {
        $allowedIps = config('app.debug_allowed_ips');

        if (empty($allowedIps)) {
            return;
        }

        $allowedIpList = array_filter(array_map('trim', explode(',', $allowedIps)));

        if (! in_array(request()->ip(), $allowedIpList)) {
            config(['debugbar.enabled' => false]);
        }
    }
}
