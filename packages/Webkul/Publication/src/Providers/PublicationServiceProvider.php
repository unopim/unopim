<?php

namespace Webkul\Publication\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Publication\DataTransferObjects\PublicationType;
use Webkul\Publication\Http\Controllers\PublicationController;
use Webkul\Publication\Http\Middleware\EnsurePublicationEnabled;
use Webkul\Publication\Http\Middleware\PublicationErrorBoundary;
use Webkul\Publication\Http\Middleware\PublicationRateLimit;
use Webkul\Publication\Registry\PublicationTypeRegistry;

class PublicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PublicationTypeRegistry::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/publication.php', 'publication');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'publication');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'publication');

        $this->app->register(ModuleServiceProvider::class);

        $this->registerPublicRoutes();
    }

    /**
     * Public so a consuming package's own provider (or a test that mutates
     * `publication.types` after this provider has already booted) can
     * re-trigger registration once its type is actually present in config.
     * Safe to call more than once as long as each type is only added to
     * config once: middleware aliases and the rate limiter definition are
     * simple overwrites, and a type present at the first call contributes
     * no routes to re-add on a later call.
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

        // Reads config directly (never database, so this stays static and
        // route:cache remains valid) rather than resolving the scoped
        // PublicationTypeRegistry singleton: that instance memoizes all()
        // permanently on first call, and consuming it here — at boot, before
        // any request-time config mutation — would freeze every request for
        // the rest of this container's lifetime onto the boot-time type list.
        foreach (collect(config('publication.types', []))->map(
            fn (array $config, string $code): PublicationType => PublicationType::fromConfig($code, $config)
        ) as $type) {
            // Deliberately NO ->whereUuid()/->where('locale', ...) constraints:
            // a route whose regex fails to match throws NotFoundHttpException
            // OUTSIDE any route's own middleware (routing itself fails before
            // a route, and therefore its group's publication.errors/enabled
            // middleware, is ever selected) — bootstrap/app.php's blanket
            // handler would render admin::errors.index for that request,
            // unconditionally, regardless of anything this package does.
            // Segment COUNT (1 vs 2 vs Task 6's 3) already disambiguates these
            // routes without needing shape constraints, and Locale.code has no
            // fixed format in this codebase (confirmed: LocaleFactory yields
            // bare 2-letter codes, not always "xx_XX") — so validating shape
            // is the controller's job, which returns a safe 404 either way.
            Route::middleware(['publication.errors', 'publication.enabled', 'publication.ratelimit'])
                ->prefix($type->routePrefix)
                ->group(function () use ($type): void {
                    Route::get('/{uuid}', [PublicationController::class, 'redirect'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show');

                    // Task 6 inserts `/{uuid}/asset/{path}` here, BEFORE the
                    // locale route below — a `{locale}` segment would
                    // otherwise swallow the literal `asset` path segment.
                    // See Task 6 Step 4 for the full, final group in
                    // registration order.

                    Route::get('/{uuid}/{locale}', [PublicationController::class, 'show'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show.locale');
                });
        }

        // RouteCollection::add() snapshots $route->getName() the instant
        // Route::get() runs, BEFORE the fluent ->name() call above has set
        // it — so the name list is only ever populated by Laravel's own
        // Application::booted() callback (Illuminate\Foundation\Support\
        // Providers\RouteServiceProvider), which fires exactly once, early
        // in the normal boot sequence. Any registration that happens after
        // that point (e.g. this method being re-invoked once a consuming
        // package's type lands in config) needs its own explicit refresh, or
        // route('publication.public.{type}.show.locale', ...) throws
        // RouteNotFoundException despite the route matching requests fine.
        $router->getRoutes()->refreshNameLookups();
    }
}
