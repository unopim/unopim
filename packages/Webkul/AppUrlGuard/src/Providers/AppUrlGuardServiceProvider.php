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

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'app_url_guard');

        if (! $kernel->hasMiddleware(VerifyAppUrlMatches::class)) {
            $kernel->pushMiddleware(VerifyAppUrlMatches::class);
        }
    }

    /**
     * Whether the guard should actually act on the current request.
     *
     * The guard is a *local developer* aid: it must stay completely out of the
     * way of the automated test suite and CI/E2E runs, where APP_URL routinely
     * differs from the request host (e.g. localhost vs 127.0.0.1, or a port)
     * and would otherwise trigger spurious banners / force-logouts that break
     * unrelated tests. A test may explicitly opt in via the
     * `app_url_guard.enabled` config to exercise the guard itself.
     */
    public static function active(): bool
    {
        $override = config('app_url_guard.enabled');

        if ($override !== null) {
            return (bool) $override;
        }

        return ! app()->runningUnitTests()
            && ! filter_var(env('CI'), FILTER_VALIDATE_BOOLEAN);
    }
}
