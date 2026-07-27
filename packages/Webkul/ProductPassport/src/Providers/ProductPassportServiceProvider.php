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
use Webkul\ProductPassport\Listeners\AutoPublishPassport;
use Webkul\ProductPassport\Listeners\ValidateProductGtin;
use Webkul\ProductPassport\View\Composers\PassportPanelComposer;

class ProductPassportServiceProvider extends ServiceProvider
{
    /**
     * Bound (not singleton) so each product-grid resolution is fresh and request-scoped.
     */
    public function register(): void
    {
        $this->app->bind(ProductDataGrid::class, PassportProductDataGrid::class);
    }

    /**
     * Boots the package: registers the `dpp` publication type and this package's settings tree.
     *
     * mergeConfigFrom is top-level non-recursive, so must boot BEFORE PublicationServiceProvider or its `types.dpp` entry is clobbered.
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

        // Reject an invalid GTIN check digit at save time rather than at publish.
        Event::listen('catalog.product.update.before', ValidateProductGtin::class);

        // Auto-publish the passport after a save when the setting is on; the listener guards to dpp-family products.
        Event::listen('catalog.product.create.after', AutoPublishPassport::class);
        Event::listen('catalog.product.update.after', AutoPublishPassport::class);

        // Guard-check here too: a globally-fired event string carries no guarantee about who triggered it.
        Event::listen('unopim.admin.catalog.product.edit.form.links.after', function ($viewRenderEventManager): void {
            if (auth()->guard('admin')->check() && bouncer()->hasPermission('catalog.passport.view')) {
                $viewRenderEventManager->addTemplate('passport::admin.catalog.products.edit.passport-panel');
            }
        });

        View::composer('passport::admin.catalog.products.edit.passport-panel', PassportPanelComposer::class);

        // Drop the Passports menu item while the feature is disabled; filtered per-request so it reacts to the setting live.
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
