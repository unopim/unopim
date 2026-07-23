<?php

namespace Webkul\Publication\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Publication\DataTransferObjects\PublicationType;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Http\Controllers\PublicationAssetController;
use Webkul\Publication\Http\Controllers\PublicationController;
use Webkul\Publication\Http\Middleware\EnsurePublicationEnabled;
use Webkul\Publication\Http\Middleware\PublicationErrorBoundary;
use Webkul\Publication\Http\Middleware\PublicationRateLimit;
use Webkul\Publication\Listeners\PrunePublicationVersionDocumentsOnRedaction;
use Webkul\Publication\Listeners\SyncPublicationVersionDocuments;
use Webkul\Publication\Registry\PublicationTypeRegistry;

class PublicationServiceProvider extends ServiceProvider
{
    /**
     * Registers the request-scoped publication type registry.
     */
    public function register(): void
    {
        $this->app->scoped(PublicationTypeRegistry::class);
    }

    /**
     * Boots the package: config, translations, migrations, views, and routes.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/publication.php', 'publication');
        $this->mergeConfigFrom(__DIR__.'/../Config/publication_settings.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../Config/system_settings.php', 'system_settings');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'publication');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'publication');

        $this->app->register(ModuleServiceProvider::class);

        Event::listen(PublicationPublished::class, SyncPublicationVersionDocuments::class);
        Event::listen(PublicationRedacted::class, PrunePublicationVersionDocumentsOnRedaction::class);

        $this->registerPublicRoutes();
    }

    /**
     * Public so a consuming provider (or a test that mutates
     * `publication.types` post-boot) can re-trigger registration. Safe to
     * call more than once: aliases/rate limiter are simple overwrites, and
     * an already-registered type contributes no routes to re-add.
     */
    public function registerPublicRoutes(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('publication.enabled', EnsurePublicationEnabled::class);
        $router->aliasMiddleware('publication.errors', PublicationErrorBoundary::class);
        $router->aliasMiddleware('publication.ratelimit', PublicationRateLimit::class);

        RateLimiter::for('publication', function (Request $request) {
            return [
                Limit::perMinute((int) (core()->getConfigData('general.publication.settings.rate_limit') ?? 60))->by($request->ip()),
                Limit::perMinute((int) config('publication.global_rate_limit'))->by('publication-global'),
            ];
        });

        // Reads config directly rather than resolving the scoped PublicationTypeRegistry:
        // that registry memoizes all() on first call, so consuming it here at boot would
        // freeze every request onto the boot-time type list.
        foreach (collect(config('publication.types', []))->map(
            fn (array $config, string $code): PublicationType => PublicationType::fromConfig($code, $config)
        ) as $type) {
            // No ->whereUuid()/->where('locale', ...) constraints on the first and
            // third routes: a route regex failure throws NotFoundHttpException
            // outside this group's own middleware, so Laravel's Pipeline renders it
            // via the global handler (admin::errors.index) instead. Segment count
            // plus the literal `asset` token disambiguate these three routes; shape
            // validation is the controller's job, which always returns our own 404.
            // The asset route's own `where('path', ...)` is a functional necessity,
            // not just a hardening extra: without it the `{path}` segment cannot
            // capture the slashes a nested document path contains at all.
            Route::middleware(['publication.errors', 'publication.enabled', 'publication.ratelimit'])
                ->prefix($type->routePrefix)
                ->group(function () use ($type): void {
                    Route::get('/{uuid}', [PublicationController::class, 'redirect'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show');

                    Route::get('/{uuid}/asset/{path}', [PublicationAssetController::class, 'show'])
                        ->where('path', '[A-Za-z0-9][A-Za-z0-9_.\/%-]*')
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.asset');

                    Route::get('/{uuid}/{locale}', [PublicationController::class, 'show'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show.locale');
                });
        }

        // RouteCollection snapshots the route name before ->name() sets it, and the
        // name-lookup cache is only rebuilt once during normal boot. A late
        // re-invocation of this method needs its own explicit refresh, or route()
        // throws RouteNotFoundException despite the route matching requests fine.
        $router->getRoutes()->refreshNameLookups();
    }
}
