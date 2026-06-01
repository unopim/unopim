<?php

namespace Webkul\AppUrlGuard\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Webkul\AppUrlGuard\Http\Middleware\VerifyAppUrlMatches;

/**
 * Registers the APP_URL developer guard.
 *
 * The VerifyAppUrlMatches middleware is appended to the global HTTP stack so
 * it runs on every web request, but it is self-gating: it does nothing unless
 * APP_DEBUG=true and the browser host differs from APP_URL. Shipping it as a
 * package keeps the guard out of the application skeleton.
 */
class AppUrlGuardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Kernel $kernel): void
    {
        if (! config('app.debug')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        if (! $kernel->hasMiddleware(VerifyAppUrlMatches::class)) {
            $kernel->pushMiddleware(VerifyAppUrlMatches::class);
        }
    }
}
