<?php

namespace Webkul\ProductPassport\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\ProductPassport\Console\InstallPassportAttributesCommand;
use Webkul\ProductPassport\DataGrids\Catalog\PassportProductDataGrid;
use Webkul\ProductPassport\Http\Controllers\PublicationController;
use Webkul\ProductPassport\View\Composers\PassportPanelComposer;

class ProductPassportServiceProvider extends ServiceProvider
{
    /**
     * Bound (not singleton) so every product-grid resolution gets a fresh,
     * request-scoped instance carrying the passport mass-publish action.
     */
    public function register(): void
    {
        $this->app->bind(ProductDataGrid::class, PassportProductDataGrid::class);
    }

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
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'passport');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'passport');

        Route::middleware('web')->group(__DIR__.'/../Routes/admin.php');

        // Gating on the admin guard matters even though the whole surrounding
        // page already requires an authenticated admin — a listener on a
        // globally-fired event string has no other guarantee about who or
        // what triggered it.
        Event::listen('unopim.admin.catalog.product.edit.form.links.after', function ($viewRenderEventManager): void {
            if (auth()->guard('admin')->check() && bouncer()->hasPermission('catalog.passport.view')) {
                $viewRenderEventManager->addTemplate('passport::admin.catalog.products.edit.passport-panel');
            }
        });

        View::composer('passport::admin.catalog.products.edit.passport-panel', PassportPanelComposer::class);

        // Opt-in feature: drop the Passports menu item while it is disabled for
        // every channel. Filtered on the resolved (per-request) menu tree so it
        // reacts to the setting live, without a config-cache-breaking condition
        // in the static menu config.
        $this->app->extend('unopim.admin.menu', function (array $menu): array {
            if (! PublicationController::featureEnabled()) {
                unset($menu['tree']->items['catalog']['children']['passport']);
            }

            return $menu;
        });

        $this->app->register(ModuleServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([InstallPassportAttributesCommand::class]);
        }
    }
}
