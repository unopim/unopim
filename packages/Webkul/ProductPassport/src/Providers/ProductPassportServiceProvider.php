<?php

namespace Webkul\ProductPassport\Providers;

use Illuminate\Support\ServiceProvider;

class ProductPassportServiceProvider extends ServiceProvider
{
    /**
     * Boots the package: registers the `dpp` publication type (merged into
     * the `publication` namespace, consumed by `Webkul\Publication`'s
     * registry) and this package's own three-level settings tree.
     *
     * Merge order matters: `mergeConfigFrom` does a top-level, non-recursive
     * `array_merge(file, existing)`, so whichever provider's `boot()` runs
     * SECOND on the shared `publication` key wins on any colliding top-level
     * key (here, `types`). This provider must boot BEFORE
     * `PublicationServiceProvider` so its `types.dpp` entry survives
     * `PublicationServiceProvider::boot()`'s own merge of `publication.php`
     * (whose own `types` default is `[]`) — verified via `route:list --path=p`.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/passport.php', 'publication');
        $this->mergeConfigFrom(__DIR__.'/../Config/passport_settings.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../Config/system_settings.php', 'system_settings');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'passport');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'passport');

        $this->app->register(ModuleServiceProvider::class);
    }
}
