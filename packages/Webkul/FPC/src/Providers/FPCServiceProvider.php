<?php

declare(strict_types=1);

namespace Webkul\FPC\Providers;

use Illuminate\Support\ServiceProvider;

class FPCServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register services.
     */
    #[\Override]
    public function register(): void {}
}
