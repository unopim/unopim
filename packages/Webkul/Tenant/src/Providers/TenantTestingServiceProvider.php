<?php

namespace Webkul\Tenant\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Tenant\Traits\TenantTesting;

/**
 * Tenant Testing Service Provider
 *
 * This service provider registers the TenantTesting trait and provides
 * configuration for tenant testing utilities.
 */
class TenantTestingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the TenantTesting trait
        $this->registerTenantTestingTrait();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/testing.php',
            'tenant.testing'
        );

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/testing.php' => config_path('tenant/testing.php'),
        ], 'tenant-config');

        // Publish documentation
        $this->publishes([
            __DIR__.'/../docs/TenantTestingUsage.md' => base_path('docs/tenant-testing.md'),
        ], 'tenant-docs');
    }

    /**
     * Register the TenantTesting trait.
     *
     * @return void
     */
    protected function registerTenantTestingTrait(): void
    {
        // No specific registration needed for traits, but we can
        // provide aliases or helpers here if needed
        if (config('app.env') === 'testing') {
            // Register commands for testing
            if ($this->app->runningInConsole()) {
                $this->commands([
                    \Webkul\Tenant\Console\Commands\TestTenantCleanupCommand::class,
                ]);
            }
        }
    }
}