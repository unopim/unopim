<?php

namespace Webkul\Completeness\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Completeness\Console\RecalculateCompletenessCommand;
use Webkul\Completeness\Helpers\Threshold;
use Webkul\Completeness\Listeners\HandleFamilyAttributeChanges;
use Webkul\Completeness\Listeners\ValidateThresholdSettings;
use Webkul\Completeness\Observers\Product as CompletenessProductObserver;
use Webkul\Product\Models\ProductProxy;

class CompletenessServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/completeness.php', 'completeness');

        $this->mergeConfigFrom(__DIR__.'/../Config/system_settings.php', 'system_settings');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'completeness');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'completeness');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(ModuleServiceProvider::class);

        ProductProxy::observe(CompletenessProductObserver::class);

        Event::listen('catalog.attribute_family.attributes.changed', HandleFamilyAttributeChanges::class);

        Event::listen('core.configuration.save.before', ValidateThresholdSettings::class);

        // Resolve the completeness colour bounds once and share them with every
        // view that colours a score, instead of repeating the lookup per view.
        View::composer([
            'admin::components.graphs.radial-progress',
            'admin::dashboard.channel-readiness',
            'admin::dashboard.product-stats',
        ], function ($view) {
            [$greenFrom, $redBelow] = app(Threshold::class)->bounds();

            $view->with('completenessGreenFrom', $greenFrom)
                ->with('completenessRedBelow', $redBelow);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                RecalculateCompletenessCommand::class,
            ]);
        }
    }
}
