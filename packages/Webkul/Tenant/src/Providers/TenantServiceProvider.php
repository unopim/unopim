<?php

namespace Webkul\Tenant\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\Tenant\Console\Commands\TenantActivateCommand;
use Webkul\Tenant\Console\Commands\TenantCreateCommand;
use Webkul\Tenant\Console\Commands\TenantDeleteCommand;
use Webkul\Tenant\Console\Commands\TenantStatusCommand;
use Webkul\Tenant\Console\Commands\TenantSuspendCommand;
use Webkul\Tenant\Http\Middleware\PlatformOperatorMiddleware;
use Webkul\Tenant\Http\Middleware\TenantMiddleware;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadRoutesFrom(__DIR__.'/../Routes/api-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'tenant');

        $this->composeView();

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'tenant');

        $this->mergeConfigFrom(__DIR__.'/../Config/tenant-roles.php', 'tenant-roles');

        $router->aliasMiddleware('tenant', TenantMiddleware::class);
        $router->aliasMiddleware('tenant.safe-errors', \Webkul\Tenant\Http\Middleware\TenantSafeErrorHandler::class);
        $router->aliasMiddleware('tenant.token', \Webkul\Tenant\Http\Middleware\TenantTokenValidator::class);
        $router->aliasMiddleware('platform.operator', PlatformOperatorMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantCreateCommand::class,
                TenantSuspendCommand::class,
                TenantActivateCommand::class,
                TenantDeleteCommand::class,
                TenantStatusCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(\Webkul\Tenant\Services\TenantSeeder::class);
        $this->app->singleton(\Webkul\Tenant\Services\TenantPurger::class);
        $this->app->singleton(\Webkul\Tenant\Auth\TenantPermissionGuard::class);
    }

    /**
     * Share tenant context with header view.
     */
    protected function composeView(): void
    {
        view()->composer([
            'admin::components.layouts.header.index',
        ], function ($view) {
            $tenantContext = null;
            $isPlatformOperator = false;
            $availableTenants = [];
            $admin = auth()->guard('admin')->user();

            if ($admin) {
                $tenantId = $admin->tenant_id ?? null;

                if ($tenantId) {
                    // Tenant admin — fixed context, only expose name (no domain/status)
                    $tenant = \Webkul\Tenant\Models\Tenant::find($tenantId);

                    $tenantContext = $tenant ? [
                        'id'   => $tenant->id,
                        'name' => $tenant->name,
                    ] : null;
                } else {
                    // Platform operator — can switch context
                    $isPlatformOperator = true;
                    $sessionTenantId = session('tenant_context_id');

                    if ($sessionTenantId) {
                        $tenant = \Webkul\Tenant\Models\Tenant::find($sessionTenantId);

                        $tenantContext = $tenant ? [
                            'id'     => $tenant->id,
                            'name'   => $tenant->name,
                            'domain' => $tenant->domain,
                            'status' => $tenant->status,
                        ] : [
                            'id'     => null,
                            'name'   => 'Platform',
                            'domain' => null,
                            'status' => 'active',
                        ];
                    } else {
                        $tenantContext = [
                            'id'     => null,
                            'name'   => 'Platform',
                            'domain' => null,
                            'status' => 'active',
                        ];
                    }

                    $availableTenants = \Webkul\Tenant\Models\Tenant::where('status', 'active')
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->get()
                        ->toArray();
                }
            }

            $view->with('tenantContext', $tenantContext);
            $view->with('isPlatformOperator', $isPlatformOperator);
            $view->with('availableTenants', $availableTenants);
        });
    }

    /**
     * Register package config (ACL, menu).
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
    }
}
