<?php

namespace Webkul\Admin\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Webkul\Admin\Console\Commands\RefreshDashboardCacheCommand;
use Webkul\Admin\Fields\FieldConfig;
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
use Webkul\MagicAI\Repository\MagicAISystemPromptRepository;
use Webkul\Product\Models\ProductProxy;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Every admin `{id}` is an auto-increment primary key, so constrain it to
        // digits group-wide: a non-numeric id yields a clean 404 instead of a 500
        // from the model lookup. Non-numeric identifiers use `code`/`slug` params.
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
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(FieldConfig::class);

        $this->app->scoped('unopim.admin.menu', fn (): array => $this->buildAdminMenu());
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
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
            dirname(__DIR__).'/Config/system_settings.php',
            'system_settings'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/help.php',
            'help'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/product_filter_operators.php',
            'product_filter_operators'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/auth.php',
            'admin.auth'
        );
    }

    /**
     * Bind the data to the views.
     */
    protected function composeView(): void
    {
        view()->composer([
            'admin::components.layouts.header.index',
            'admin::components.layouts.sidebar.index',
            'admin::components.layouts.tabs',
            'admin::components.breadcrumbs',
        ], function ($view) {
            ['tree' => $tree, 'landingUrl' => $landingUrl] = app('unopim.admin.menu');

            $view->with('menu', $tree);
            $view->with('adminLandingUrl', $landingUrl);
        });

        view()->composer([
            'admin::settings.roles.create',
            'admin::settings.roles.edit',
        ], function ($view) {
            $view->with('acl', $this->createACL());
        });

        view()->composer('admin::components.tinymce.index', function ($view) {
            $systemPrompts = once(fn () => app(MagicAISystemPromptRepository::class)->all()->toArray());

            $view->with('systemPrompts', $systemPrompts);
        });
    }

    /**
     * Build the authorized admin sidebar menu once per request. Shared by the
     * header, sidebar, tabs and breadcrumb views via the `unopim.admin.menu`
     * scoped binding instead of rebuilding the tree for each.
     *
     * @return array{tree: Tree, landingUrl: string}
     */
    protected function buildAdminMenu(): array
    {
        $tree = Tree::create();

        foreach (config('menu.admin') as $item) {
            if (! bouncer()->hasPermission($item['key'])) {
                continue;
            }

            $tree->add($item, 'menu');
        }

        $tree->items = core()->sortItems($tree->items);
        $tree->items = $tree->removeUnauthorizedUrls();

        if (! $tree->currentKey) {
            $tree->currentKey = $this->resolveActiveMenuKey();
        }

        $landing = collect($tree->items)->first(fn (array $item): bool => ! empty($item['url']));

        return [
            'tree'       => $tree,
            'landingUrl' => $landing['url'] ?? route('admin.session.create'),
        ];
    }

    /**
     * Resolve the sidebar menu key for an off-menu route whose URL prefix-matched
     * no menu item (e.g. a detail route `admin.magic_ai.prompt.edit` or a hub page
     * like appearance). First matches the current route name against menu items by
     * route-name ancestry (most specific wins), then falls back to the System
     * Settings hub mapping.
     */
    protected function resolveActiveMenuKey(): ?string
    {
        $currentRoute = Route::currentRouteName();

        if (! $currentRoute) {
            return null;
        }

        $bestKey = null;
        $bestLength = 0;

        foreach (config('menu.admin') as $item) {
            if (empty($item['route']) || empty($item['key'])) {
                continue;
            }

            $routeBase = Str::beforeLast($item['route'], '.');

            if ($currentRoute === $item['route'] || Str::startsWith($currentRoute, $routeBase.'.')) {
                if (strlen($routeBase) > $bestLength) {
                    $bestLength = strlen($routeBase);
                    $bestKey = $item['key'];
                }
            }
        }

        return $bestKey ?? $this->resolveHubMenuKey();
    }

    /**
     * Resolve the sidebar menu key that owns the current System Settings hub
     * route. Returns the parent menu key (the hub row's `acl`) so off-menu hub
     * pages activate their sidebar group, or null when the current route is not
     * a hub page.
     */
    protected function resolveHubMenuKey(): ?string
    {
        $currentRoute = Route::currentRouteName();

        if (! $currentRoute) {
            return null;
        }

        foreach (config('system_settings') as $item) {
            if (empty($item['route']) || empty($item['acl'])) {
                continue;
            }

            $routeBase = Str::beforeLast($item['route'], '.');

            if ($currentRoute === $item['route'] || Str::startsWith($currentRoute, $routeBase.'.')) {
                return $item['acl'];
            }
        }

        return null;
    }

    /**
     * Register ACL to entire application.
     */
    protected function registerACL(): void
    {
        $this->app->singleton('acl', function () {
            return $this->createACL();
        });
    }

    /**
     * Create ACL tree.
     */
    protected function createACL(): Tree
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
            $maxAttempts = config('admin.auth.login_rate_limit', 5);

            return Limit::perMinute(is_numeric($maxAttempts) ? (int) $maxAttempts : 5)->by($key);
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
