<?php

namespace Webkul\Admin\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\Console\Commands\RefreshDashboardCacheCommand;
use Webkul\Admin\Observers\CategoryObserver;
use Webkul\Admin\Observers\ConfigurationObserver;
use Webkul\Admin\Observers\ProductObserver;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CurrencyProxy;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Core\Tree;
use Webkul\Product\Models\ProductProxy;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Route::middleware('web')
            ->where(['id' => '[0-9]+'])
            ->group(__DIR__.'/../Routes/web.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'admin');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'admin');

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'admin');

        $this->composeView();

        $this->registerACL();

        $this->app->register(EventServiceProvider::class);

        ProductProxy::observe(ProductObserver::class);

        CategoryProxy::observe(CategoryObserver::class);

        AttributeProxy::observe(ConfigurationObserver::class);
        AttributeGroupProxy::observe(ConfigurationObserver::class);
        AttributeFamilyProxy::observe(ConfigurationObserver::class);
        LocaleProxy::observe(ConfigurationObserver::class);
        ChannelProxy::observe(ConfigurationObserver::class);
        CurrencyProxy::observe(ConfigurationObserver::class);

        Event::listen('unopim.admin.layout.content.before', function ($viewRenderEventManager) {
            if (auth()->guard('admin')->check()) {
                $viewRenderEventManager->addTemplate('admin::promo.bar');
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshDashboardCacheCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php',
            'core'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/help.php',
            'help'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/auth.php',
            'admin.auth'
        );
    }

    /**
     * Bind the data to the views.
     *
     * @return void
     */
    protected function composeView()
    {
        view()->composer([
            'admin::components.layouts.header.index',
            'admin::components.layouts.sidebar.index',
            'admin::components.layouts.tabs',
        ], function ($view) {
            $tree = Tree::create();

            foreach (config('menu.admin') as $index => $item) {
                if (! bouncer()->hasPermission($item['key'])) {
                    continue;
                }

                $tree->add($item, 'menu');
            }

            $tree->items = core()->sortItems($tree->items);
            $tree->items = $tree->removeUnauthorizedUrls();

            $landingUrl = null;

            foreach ($tree->items as $item) {
                if (! empty($item['url'])) {
                    $landingUrl = $item['url'];
                    break;
                }
            }

            $view->with('menu', $tree);
            $view->with('adminLandingUrl', $landingUrl ?? route('admin.session.create'));
        });

        view()->composer([
            'admin::settings.roles.create',
            'admin::settings.roles.edit',
        ], function ($view) {
            $view->with('acl', $this->createACL());
        });
    }

    /**
     * Register ACL to entire application.
     *
     * @return void
     */
    protected function registerACL()
    {
        $this->app->singleton('acl', function () {
            return $this->createACL();
        });
    }

    /**
     * Create ACL tree.
     *
     * @return mixed
     */
    protected function createACL()
    {
        static $tree;

        if ($tree) {
            return $tree;
        }

        $tree = Tree::create();

        foreach (config('acl') as $item) {
            $tree->add($item, 'acl');
        }

        $tree->items = core()->sortItems($tree->items);

        return $tree;
    }

    /**
     * Configure rate limiters for admin authentication routes.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            $key = strtolower(trim((string) $request->input('email', ''))).'|'.$request->ip();

            // No ->response() override: let the throttle middleware throw a real 429
            // so the Core exception handler renders the branded 429 page (HTML) or a
            // {error, description} 429 payload (JSON) — see LoginThrottleErrorPageTest.
            return Limit::perMinute((int) config('admin.auth.login_rate_limit', 5))->by($key);
        });

        RateLimiter::for('admin-forgot-password', function (Request $request) {
            $key = strtolower(trim((string) $request->input('email', ''))).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('admin-reset-password', function (Request $request) {
            $key = strtolower(trim((string) $request->input('email', ''))).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('admin-sso', function (Request $request) {
            $sessionId = optional($request->session())->getId() ?: 'guest';
            $key = $sessionId.'|'.$request->ip();

            return Limit::perMinute(20)->by($key);
        });
    }
}
