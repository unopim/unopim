<?php

namespace Webkul\AdminApi\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\UserRepository as PassportUserRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Webkul\AdminApi\Console\ApiClientCommand;
use Webkul\AdminApi\Http\Middleware\EnsureAcceptsJson;
use Webkul\AdminApi\Http\Middleware\LocaleMiddleware;
use Webkul\AdminApi\Http\Middleware\ScopeMiddleware;
use Webkul\AdminApi\Models\Client;
use Webkul\AdminApi\Repositories\UserRepository;
use Webkul\Core\Tree;
use Webkul\User\Models\Admin;

class AdminApiServiceProvider extends ServiceProvider
{
    /**
     * Register your middleware aliases here.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'accept.json'    => EnsureAcceptsJson::class,
        'request.locale' => LocaleMiddleware::class,
        'api.scope'      => ScopeMiddleware::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/integrations-routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->activateMiddlewareAliases();
        $this->activatePassportApiClient();

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'admin_api');
        $this->composeView();
        $this->registerACL();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
        $this->registerApiRoutes();
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
            dirname(__DIR__).'/Config/api.php', 'api'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/api-acl.php', 'api-acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php', 'menu.admin'
        );
    }

    /**
     * Define the "api" routes for the application.
     *
     * @return void
     */
    protected function registerApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/admin-api.php');

    }

    /**
     * Register the Installer Commands of this package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ApiClientCommand::class,
            ]);
        }
    }

    /**
     * Register the Installer Commands of this package.
     */
    protected function activatePassportApiClient(): void
    {
        Passport::loadKeysFrom(__DIR__.'/../Secrets/Oauth');

        Passport::$passwordGrantEnabled = true;
        Passport::setClientUuids(true);
        Passport::useClientModel(Client::class);

        // Register a custom UserRepository that uses the Admin model instead of App\Models\User
        // This ensures that Passport's OAuth2 password grant correctly authenticates admin users
        $this->app->singleton(PassportUserRepository::class, function ($app) {
            return new UserRepository($app->make('hash'));
        });

        $accessTokenTtl = (int) config('api.access_token_ttl', 3600);
        $refreshTokenTtl = (int) config('api.refresh_token_ttl', 3600);

        // Set access token TTL
        Passport::tokensExpireIn(Carbon::now()->addSeconds($accessTokenTtl));

        // Set refresh token TTL
        Passport::refreshTokensExpireIn(Carbon::now()->addSeconds($refreshTokenTtl));

        $this->app->bind(ClientRepository::class, \Webkul\AdminApi\Repositories\ClientRepository::class);
    }

    /**
     * Activate middleware aliases.
     *
     * @return void
     */
    protected function activateMiddlewareAliases()
    {
        collect($this->middlewareAliases)->each(function ($className, $alias) {
            $this->app['router']->aliasMiddleware($alias, $className);
        });
    }

    /**
     * Bind the the data to the views
     *
     * @return void
     */
    protected function composeView()
    {
        view()->composer([
            'admin_api::integrations.api-keys.create',
            'admin_api::integrations.api-keys.edit',
        ], function ($view) {
            $view->with('acl', $this->createACL());
        });
    }

    /**
     * Registers acl to entire application
     *
     * @return void
     */
    public function registerACL()
    {
        $this->app->singleton('api-acl', function () {
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

        foreach (config('api-acl') as $item) {
            $tree->add($item, 'acl');
        }

        $tree->items = core()->sortItems($tree->items);

        return $tree;
    }
}
