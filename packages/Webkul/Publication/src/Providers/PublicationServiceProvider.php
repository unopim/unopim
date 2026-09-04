<?php

namespace Webkul\Publication\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Publication\Contracts\LotReleaseResolver;
use Webkul\Publication\DataTransferObjects\PublicationType;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Events\PublicationReinstated;
use Webkul\Publication\Events\PublicationWithdrawn;
use Webkul\Publication\Http\Controllers\PublicationAssetController;
use Webkul\Publication\Http\Controllers\PublicationCarrierController;
use Webkul\Publication\Http\Controllers\PublicationController;
use Webkul\Publication\Http\Middleware\EnsurePublicationEnabled;
use Webkul\Publication\Http\Middleware\PublicationErrorBoundary;
use Webkul\Publication\Http\Middleware\PublicationRateLimit;
use Webkul\Publication\Http\Middleware\SecurePublicHeaders;
use Webkul\Publication\Listeners\GuardChannelDeletionAgainstPublications;
use Webkul\Publication\Listeners\GuardProductDeletionAgainstPublications;
use Webkul\Publication\Listeners\PrunePublicationVersionDocumentsOnRedaction;
use Webkul\Publication\Listeners\SyncPublicationCounters;
use Webkul\Publication\Listeners\SyncPublicationGtin;
use Webkul\Publication\Listeners\SyncPublicationVersionDocuments;
use Webkul\Publication\Registry\PublicationTypeRegistry;
use Webkul\Publication\Services\Gs1DigitalLink;
use Webkul\Publication\Services\NullLotReleaseResolver;

class PublicationServiceProvider extends ServiceProvider
{
    /**
     * Registers the request-scoped publication type registry.
     */
    public function register(): void
    {
        // Consumers with batch/ERP knowledge rebind this; the engine itself cannot know which release a lot shipped under.
        $this->app->bindIf(LotReleaseResolver::class, NullLotReleaseResolver::class);

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
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'publication');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'publication');

        $this->app->register(ModuleServiceProvider::class);

        Event::listen(PublicationPublished::class, SyncPublicationVersionDocuments::class);
        Event::listen(PublicationPublished::class, SyncPublicationCounters::class);
        Event::listen(PublicationWithdrawn::class, SyncPublicationCounters::class);
        Event::listen(PublicationReinstated::class, SyncPublicationCounters::class);
        Event::listen(PublicationPublished::class, SyncPublicationGtin::class);
        Event::listen(PublicationRedacted::class, PrunePublicationVersionDocumentsOnRedaction::class);
        Event::listen('catalog.product.delete.before', GuardProductDeletionAgainstPublications::class);
        Event::listen('core.channel.delete.before', GuardChannelDeletionAgainstPublications::class);

        $this->registerPublicRoutes();
    }

    /**
     * Public and idempotent so a consuming provider or a post-boot test can re-trigger registration.
     */
    public function registerPublicRoutes(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('publication.enabled', EnsurePublicationEnabled::class);
        $router->aliasMiddleware('publication.errors', PublicationErrorBoundary::class);
        $router->aliasMiddleware('publication.ratelimit', PublicationRateLimit::class);
        $router->aliasMiddleware('publication.headers', SecurePublicHeaders::class);

        RateLimiter::for('publication', function (Request $request) {
            return [
                Limit::perMinute((int) (core()->getConfigData('general.publication.settings.rate_limit') ?? 60))->by($request->ip()),
                Limit::perMinute((int) config('publication.global_rate_limit'))->by('publication-global'),
            ];
        });

        // Read config directly: the scoped registry memoizes all() and would freeze requests onto the boot-time list.
        foreach (collect(config('publication.types', []))->map(
            fn (array $config, string $code): PublicationType => PublicationType::fromConfig($code, $config)
        ) as $type) {
            // Shape validation is the controller's job; a regex miss 404s via the global handler, not ours.
            // The asset route's where('path') is functional: without it `{path}` can't capture nested slashes.
            Route::middleware(['publication.errors', 'publication.enabled', 'publication.headers', 'publication.ratelimit'])
                ->prefix($type->routePrefix)
                ->group(function () use ($type): void {
                    Route::get('/{uuid}', [PublicationController::class, 'redirect'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show');

                    Route::get('/{uuid}/asset/{path}', [PublicationAssetController::class, 'show'])
                        ->where('path', '[A-Za-z0-9][A-Za-z0-9_.\/%-]*')
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.asset');

                    /**
                     * Extensionless: a `.svg` suffix is intercepted by the static-file
                     * rules in a typical nginx vhost and never reaches PHP. Registered
                     * before `/{uuid}/{locale}` so first-match resolves it here rather
                     * than as a locale, and the suffixed path stays as an alias for
                     * carriers already printed on a product.
                     */
                    Route::get('/{uuid}/carrier', [PublicationCarrierController::class, 'show'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.carrier');

                    Route::get('/{uuid}/carrier.svg', [PublicationCarrierController::class, 'show'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.carrier.svg');

                    // Four segments with a literal `r`, so it can never shadow the two-segment routes above.
                    // `sequence` is bounded to a positive int that fits the column; anything else 404s at the router.
                    // Entry point a printed release carrier encodes: negotiates the locale once, then 302s to the strict URL.
                    Route::get('/{uuid}/r/{sequence}', [PublicationController::class, 'redirectRelease'])
                        ->where('sequence', '[1-9][0-9]{0,9}')
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show.release.entry');

                    Route::get('/{uuid}/r/{sequence}/{locale}', [PublicationController::class, 'showRelease'])
                        ->where('sequence', '[1-9][0-9]{0,9}')
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show.release');

                    Route::get('/{uuid}/{locale}', [PublicationController::class, 'show'])
                        ->defaults('type', $type->code)
                        ->name('publication.public.'.$type->code.'.show.locale');
                });
        }

        // GS1 Digital Link's `/01/{gtin}` grammar is standard-fixed, not per-type-prefixed, so it lives in its own
        // group; the numeric regex keeps it from shadowing per-type routes (which all start with an alphabetic segment).
        Route::middleware(['publication.errors', 'publication.enabled', 'publication.headers', 'publication.ratelimit'])
            ->group(function (): void {
                Route::get('/01/{gtin}', [PublicationController::class, 'resolveByGtin'])
                    ->where('gtin', Gs1DigitalLink::GTIN_PATTERN)
                    ->defaults('type', 'dpp')
                    ->name('publication.public.gs1');

                // GS1 qualifiers (lot = AI 10, serial = AI 21, in that order when both). The URI grammar only
                // bounds the segment here; the 82-character set and 1-20 length are enforced in the controller.
                foreach (['/01/{gtin}/10/{lot}' => 'gs1.lot', '/01/{gtin}/21/{serial}' => 'gs1.serial', '/01/{gtin}/10/{lot}/21/{serial}' => 'gs1.lot.serial'] as $uri => $name) {
                    Route::get($uri, [PublicationController::class, 'resolveByGtinQualified'])
                        ->where(['gtin' => Gs1DigitalLink::GTIN_PATTERN, 'lot' => '[^\/]{1,80}', 'serial' => '[^\/]{1,80}'])
                        ->defaults('type', 'dpp')
                        ->name('publication.public.'.$name);
                }
            });

        // A late re-invocation needs an explicit refresh, or route() throws despite the routes matching requests fine.
        $router->getRoutes()->refreshNameLookups();
    }
}
